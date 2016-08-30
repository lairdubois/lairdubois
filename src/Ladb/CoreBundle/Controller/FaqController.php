<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Manager\Faq\QuestionManager;
use Ladb\CoreBundle\Manager\WitnessManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Faq\Question;
use Ladb\CoreBundle\Form\Type\Faq\QuestionType;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\PublicationUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\ReportableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;

/**
 * @Route("/faq")
 */
class FaqController extends Controller {

	/**
	 * @Route("/new", name="core_faq_question_new")
	 * @Template()
	 */
	public function newAction() {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_faq_question_new)');
		}

		$question = new Question();
		$question->addBodyBlock(new \Ladb\CoreBundle\Entity\Block\Text());	// Add a default Text body block
		$form = $this->createForm(QuestionType::class, $question);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($question),
		);
	}

	/**
	 * @Route("/create", name="core_faq_question_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Faq:new.html.twig")
	 */
	public function createAction(Request $request) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_faq_question_create)');
		}

		$om = $this->getDoctrine()->getManager();

		$question = new Question();
		$form = $this->createForm(QuestionType::class, $question);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($question);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($question);

			$question->setUser($this->getUser());

			$om->persist($question);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($question));

			return $this->redirect($this->generateUrl('core_faq_question_show', array( 'id' => $question->getSluggedId() )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'question'     => $question,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($question),
		);
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_faq_question_publish")
	 */
	public function publishAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_faq_question_publish)');
		}

		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);

		$question = $questionRepository->findOneByIdJoinedOnUser($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}
		if ($question->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_faq_question_publish)');
		}

		// Publish
		$questionManager = $this->get(QuestionManager::NAME);
		$questionManager->publish($question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('faq.question.form.alert.publish_success', array( '%title%' => $question->getTitle() )));

		return $this->redirect($this->generateUrl('core_faq_question_show', array( 'id' => $question->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_faq_question_unpublish")
	 */
	public function unpublishAction(Request $request, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_faq_question_unpublish)');
		}

		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);

		$question = $questionRepository->findOneByIdJoinedOnUser($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}
		if ($question->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_faq_question_unpublish)');
		}

		// Unpublish
		$questionManager = $this->get(QuestionManager::NAME);
		$questionManager->unpublish($question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('faq.question.form.alert.unpublish_success', array( '%title%' => $question->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_faq_question_edit")
	 * @Template()
	 */
	public function editAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_faq_question_edit)');
		}

		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);

		$question = $questionRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}

		$form = $this->createForm(QuestionType::class, $question);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'question'     => $question,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($question),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, name="core_faq_question_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Faq:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_faq_question_update)');
		}

		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);

		$question = $questionRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}

		$originalBodyBlocks = $question->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $question->getTags()->toArray();	// Need to be an array to copy values

		$question->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(QuestionType::class, $question);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($question, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($question);

			$question->setUpdatedAt(new \DateTime());

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($question, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('faq.question.form.alert.update_success', array( '%title%' => $question->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(QuestionType::class, $question);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'question'     => $question,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($question),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_faq_question_delete")
	 */
	public function deleteAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_faq_question_delete)');
		}

		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);

		$question = $questionRepository->findOneById($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}

		// Delete
		$questionManager = $this->get(QuestionManager::NAME);
		$questionManager->delete($question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('faq.question.form.alert.delete_success', array( '%title%' => $question->getTitle() )));

		return $this->redirect($this->generateUrl('core_faq_question_list'));
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_faq_question_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_faq_question_list_filter_page")
	 * @Template()
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_faq_question_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_faq_question_list_page")
	 * @Template()
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				switch ($facet->name) {

					case 'tag':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'tags.name' ));
						$filters[] = $filter;

						break;

					case 'sort':

						switch ($facet->value) {

							case 'recent':
								$sort = array( 'changedAt' => array( 'order' => 'desc' ) );
								break;

							case 'important':
								$sort = array( 'weight' => array( 'order' => 'desc' ) );
								break;

							case 'popular-views':
								$sort = array( 'viewCount' => array( 'order' => 'desc' ) );
								break;

							case 'popular-likes':
								$sort = array( 'likeCount' => array( 'order' => 'desc' ) );
								break;

							case 'popular-comments':
								$sort = array( 'commentCount' => array( 'order' => 'desc' ) );
								break;

						}

						break;

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'title', 'body', 'tags.name' ));
							$filters[] = $filter;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'weight' => array( 'order' => 'desc' ) );

			},
			'fos_elastica.index.ladb.question',
			\Ladb\CoreBundle\Entity\Faq\Question::CLASS_NAME,
			'core_faq_question_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities']));

		$parameters = array_merge($searchParameters, array(
			'questions' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Faq:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", requirements={"slug" = "[a-z-]+"}, name="core_faq_question_show")
	 * @Template()
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		if (intval($id) == 0) {
			$question = $questionRepository->findOneBySlugJoinedOnAll($id);
		} else {
			$id = intval($id);
			$question = $questionRepository->findOneById($id);
		}
		if (is_null($question)) {
			if ($response = $witnessManager->checkResponse(Question::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}
		if ($question->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
				if ($response = $witnessManager->checkResponse(Question::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_faq_question_show)');
			}
		}

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$similarQuestions = $explorableUtils->getSimilarExplorables($question, 'fos_elastica.index.ladb.question', Question::CLASS_NAME, null, 10);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($question));

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'question'         => $question,
			'similarQuestions' => $similarQuestions,
			'likeContext'      => $likableUtils->getLikeContext($question, $this->getUser()),
			'watchContext'     => $watchableUtils->getWatchContext($question, $this->getUser()),
			'commentContext'   => $commentableUtils->getCommentContext($question),
			'followerContext'  => $followerUtils->getFollowerContext($question->getUser(), $this->getUser()),
		);
	}

}
