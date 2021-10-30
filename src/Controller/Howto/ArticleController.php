<?php

namespace App\Controller\Howto;

use App\Controller\PublicationControllerTrait;
use App\Entity\Howto\Article;
use App\Entity\Howto\Howto;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Form\Type\Howto\HowtoArticleType;
use App\Manager\Core\WitnessManager;
use App\Manager\Howto\ArticleManager;
use App\Utils\BlockBodiedUtils;
use App\Utils\EmbeddableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\MentionUtils;
use App\Utils\SearchUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ArticleController extends AbstractHowtoBasedController {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.WitnessManager::class,
            '?'.ArticleManager::class,
            '?'.BlockBodiedUtils::class,
            '?'.FieldPreprocessorUtils::class,
            '?'.MentionUtils::class,
            '?'.SearchUtils::class,
        ));
    }

	use PublicationControllerTrait;

	private function _updateHowtoBlockVideoCount($howto) {
		$bodyBlockVideoCount = 0;
		foreach ($howto->getArticles() as $article) {
			if ($article->getIsDraft()) {
				continue;
			}
			$bodyBlockVideoCount += $article->getBodyBlockVideoCount();
		}
		$howto->setBodyBlockVideoCount($bodyBlockVideoCount);
	}

	/////

	/**
	 * @Route("/pas-a-pas/{id}/articles/new", requirements={"id" = "\d+"}, name="core_howto_article_new")
	 * @Template("Howto/Article/new.html.twig")
	 */
	public function new(Request $request, $id) {

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertEditabable($howto);

		$article = new Article();
		$article->addBodyBlock(new \App\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(HowtoArticleType::class, $article);

		return array(
			'howto' => $howto,
			'owner' => $this->retrieveOwner($request),
			'form'  => $form->createView(),
		);
	}

	/**
	 * @Route("/pas-a-pas/{id}/articles/create", requirements={"id" = "\d+"}, methods={"POST"}, name="core_howto_article_create")
	 * @Template("Howto/Article/new.html.twig")
	 */
	public function create(Request $request, $id) {

		$this->createLock('core_howto_article_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertEditabable($howto);

		$article = new Article();
		$article->setHowto($howto);	// Used by ArticleBodyValidator
		$form = $this->createForm(HowtoArticleType::class, $article);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($article);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($article);

			$article->setSortIndex($howto->getArticleMaxSortIndex() + 1);	// Default sort index is max value = new articles at the end of the list
			$howto->addArticle($article);
			if ($howto->getIsDraft()) {
				$article->setIsDraft(false);
				$howto->incrementPublishedArticleCount();
			} else {
				$howto->incrementDraftArticleCount();
			}

			$om->persist($article);
			$om->flush();

			return $this->redirect($this->generateUrl('core_howto_edit', array('id' => $howto->getId())).'#articles');
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		return array(
			'howto' => $howto,
			'form'  => $form->createView(),
			'owner' => $this->retrieveOwner($request),
		);
	}

	/**
	 * @Route("/pas-a-pas/articles/{id}/publish", requirements={"id" = "\d+"}, name="core_howto_article_publish")
	 */
	public function publish($id) {

		$article = $this->retrievePublication($id, Article::CLASS_NAME);
		$this->assertPublishable($article);

		// Publish
		$articleManager = $this->get(ArticleManager::class);
		$articleManager->publish($article);

		// Process mentions
		$mentionUtils = $this->get(MentionUtils::class);
		$mentionUtils->processMentions($article);

		$howto = $article->getHowto();
		$this->_updateHowtoBlockVideoCount($howto);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.article.form.alert.publish_success', array( '%title%' => $article->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $article->getHowto()->getSluggedId() )).'#'.$article->getSluggedId());
	}

	/**
	 * @Route("/pas-a-pas/articles/{id}/unpublish", requirements={"id" = "\d+"}, name="core_howto_article_unpublish")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_howto_article_unpublish)")
	 */
	public function unpublish(Request $request, $id) {

		$article = $this->retrievePublication($id, Article::CLASS_NAME);
		$this->assertUnpublishable($article->getHowto(), true);

		// Unpublish
		$articleManager = $this->get(ArticleManager::class);
		$articleManager->unpublish($article);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.article.form.alert.unpublish_success', array( '%title%' => $article->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/pas-a-pas/articles/{id}/edit", requirements={"id" = "\d+"}, name="core_howto_article_edit")
	 * @Template("Howto/Article/edit.html.twig")
	 */
	public function edit(Request $request, $id) {

		$article = $this->retrievePublication($id, Article::CLASS_NAME);
		$this->assertEditabable($article->getHowto());

		$form = $this->createForm(HowtoArticleType::class, $article);

		// Return to

		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return array(
			'howto' => $article->getHowto(),
			'article' => $article,
			'form'    => $form->createView(),
			'rtu'     => $returnToUrl,
		);
	}

	/**
	 * @Route("/pas-a-pas/articles/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_howto_article_update")
	 * @Template("Howto/Article/edit.html.twig")
	 */
	public function update(Request $request, $id) {

		$article = $this->retrievePublication($id, Article::CLASS_NAME);
		$this->assertEditabable($article->getHowto());

		$originalBodyBlocks = $article->getBodyBlocks()->toArray();	// Need to be an array to copy values

		$article->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(HowtoArticleType::class, $article);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($article, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($article);

			$embaddableUtils = $this->get(EmbeddableUtils::class);
			$embaddableUtils->resetSticker($article);

			$howto = $article->getHowto();

			if ($howto->getUser() == $this->getUser()) {
				$article->setUpdatedAt(new \DateTime());
				$howto->setUpdatedAt(new \DateTime());
			}
			$this->_updateHowtoBlockVideoCount($howto);

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Process mentions
			$mentionUtils = $this->get(MentionUtils::class);
			$mentionUtils->processMentions($article);

			// Search index update
			$searchUtils = $this->get(SearchUtils::class);
			$searchUtils->replaceEntityInIndex($howto);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.article.form.alert.update_success', array( '%title%' => $article->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(HowtoArticleType::class, $article);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		return array(
			'rtu'     => $request->get('rtu'),
			'howto'   => $article->getHowto(),
			'article' => $article,
			'form'    => $form->createView(),
		);
	}

	/**
	 * @Route("/pas-a-pas/articles/{id}/delete", requirements={"id" = "\d+"}, name="core_howto_article_delete")
	 */
	public function delete($id) {
		$om = $this->getDoctrine()->getManager();

		$article = $this->retrievePublication($id, Article::CLASS_NAME);
		$this->assertDeletable($article->getHowto(), true);

		$howto = $article->getHowto();

		// Delete
		$articleManager = $this->get(ArticleManager::class);
		$articleManager->delete($article, true, false);

		$this->_updateHowtoBlockVideoCount($howto);

		$om->flush();

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->replaceEntityInIndex($howto);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.article.form.alert.delete_success', array( '%title%' => $article->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_edit', array( 'id' => $howto->getId() )));
	}

	/**
	 * @Route("/pas-a-pas/articles/{id}/widget", requirements={"id" = "\d+"}, name="core_howto_article_widget")
	 * @Template("Howto/Article/widget-xhr.html.twig")
	 */
	public function widget(Request $request, $id) {

		$id = intval($id);

		$article = $this->retrievePublication($id, Article::CLASS_NAME);
		$this->assertShowable($article->getHowto(), true);

		return array(
			'article' => $article,
		);
	}

	/**
	 * @Route("/pas-a-pas/article/{id}/sticker.png", requirements={"id" = "\d+"}, name="core_howto_article_sticker_bc")
	 */
	public function bcSticker(Request $request, $id) {
		return $this->redirect($this->generateUrl('core_howto_article_sticker', array( 'id' => $id )));
	}

	/**
	 * @Route("/pas-a-pas/articles/{id}/sticker", requirements={"id" = "\d+"}, name="core_howto_article_sticker")
	 */
	public function sticker(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$articleRepository = $om->getRepository(Article::CLASS_NAME);

		$id = intval($id);

		$article = $articleRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($article)) {
			throw $this->createNotFoundException('Unable to find Article entity (id='.$id.').');
		}
		if ($article->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_howto_article_sticker)');
		}
		if ($article->getBodyBlockPictureCount() == 0) {
			throw $this->createNotFoundException('No picture, No sticker !');
		}

		$sticker = $article->getSticker();
		if (is_null($sticker)) {
			$embeddableUtils = $this->get(EmbeddableUtils::class);
			$sticker = $embeddableUtils->generateSticker($article);
			if (!is_null($sticker)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating sticker (core_howto_article_sticker)');
			}
		}

		if (!is_null($sticker)) {

			$response = $this->get('liip_imagine.controller')->filterAction($request, $sticker->getWebPath(), '598w');
			return $response;

		} else {
			throw $this->createNotFoundException('No sticker');
		}

	}

	/**
	 * @Route("/pas-a-pas/article/{id}.html", name="core_howto_article_show_bc")
	 */
	public function bcShow(Request $request, $id) {
		return $this->redirect($this->generateUrl('core_howto_article_show', array( 'id' => $id )));
	}

	/**
	 * @Route("/pas-a-pas/articles/{id}.html", name="core_howto_article_show")
	 * @Template("Howto/Article/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$articleRepository = $om->getRepository(Article::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$article = $articleRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($article)) {
			if ($response = $witnessManager->checkResponse(Article::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Article entity (id='.$id.').');
		}
		$howto = $article->getHowto();
		$this->assertShowable($article->getHowto());

		$mainPicture = null;

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($howto), PublicationListener::PUBLICATION_SHOWN);

		$parameters = array_merge($this->computeShowParameters($howto, $request), array(
			'article'     => $article,
			'mainPicture' => $mainPicture,
		));

		return $parameters;
	}

}