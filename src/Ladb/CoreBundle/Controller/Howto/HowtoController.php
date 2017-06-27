<?php

namespace Ladb\CoreBundle\Controller\Howto;

use Ladb\CoreBundle\Utils\HowtoUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Wonder\Workshop;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Howto\Article;
use Ladb\CoreBundle\Entity\Knowledge\Provider;
use Ladb\CoreBundle\Form\Type\Howto\HowtoType;
use Ladb\CoreBundle\Form\Type\Howto\HowtoArticleType;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\EmbeddableUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Manager\Howto\ArticleManager;
use Ladb\CoreBundle\Manager\Howto\HowtoManager;
use Ladb\CoreBundle\Manager\WitnessManager;

class HowtoController extends Controller {

	/**
	 * @Route("/pas-a-pas/new", name="core_howto_new")
	 * @Template()
	 */
	public function newAction() {

		$howto = new Howto();
		$form = $this->createForm(HowtoType::class, $howto);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($howto),
		);
	}

	/**
	 * @Route("/pas-a-pas/create", name="core_howto_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Howto/Howto:new.html.twig")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$howto = new Howto();
		$form = $this->createForm(HowtoType::class, $howto);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($howto);

			$howto->setUser($this->getUser());
			$this->getUser()->incrementDraftHowtoCount();

			$om->persist($howto);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($howto));

			return $this->redirect($this->generateUrl('core_howto_article_new', array('id' => $howto->getId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'howto'        => $howto,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($howto),
			'hideWarning'  => true,
		);
	}

	/////

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_howto_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_howto_unlock")
	 */
	public function lockUnlockAction($id, $lock) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_howto_lock or core_howto_unlock)');
		}
		if ($howto->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_howto_lock or core_howto_unlock)');
		}

		// Lock or Unlock
		$howtoManager = $this->get(HowtoManager::NAME);
		if ($lock) {
			$howtoManager->lock($howto);
		} else {
			$howtoManager->unlock($howto);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $howto->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

	/**
	 * @Route("/pas-a-pas/{id}/publish", requirements={"id" = "\d+"}, name="core_howto_publish")
	 */
	public function publishAction($id) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $howto->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_howto_publish)');
		}
		if ($howto->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_howto_publish)');
		}
		if ($howto->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked (core_howto_publish)');
		}
		if ($howto->getPublishedArticleCount() == 0) {
			throw $this->createNotFoundException('Not enough published articles');
		}

		// Publish
		$howtoManager = $this->get(HowtoManager::NAME);
		$howtoManager->publish($howto);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.form.alert.publish_success', array( '%title%' => $howto->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

	/**
	 * @Route("/pas-a-pas/{id}/unpublish", requirements={"id" = "\d+"}, name="core_howto_unpublish")
	 */
	public function unpublishAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_howto_unpublish)');
		}
		if ($howto->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_howto_unpublish)');
		}

		// Unpublish
		$howtoManager = $this->get(HowtoManager::NAME);
		$howtoManager->unpublish($howto);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.form.alert.unpublish_success', array( '%title%' => $howto->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/pas-a-pas/{id}/edit", requirements={"id" = "\d+"}, name="core_howto_edit")
	 * @Template()
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $howto->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_howto_edit)');
		}

		$form = $this->createForm(HowtoType::class, $howto);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'howto'        => $howto,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($howto),
		);
	}

	/**
	 * @Route("/pas-a-pas/{id}/update", requirements={"id" = "\d+"}, name="core_howto_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Howto/Howto:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $howto->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_howto_update)');
		}

		$howto->resetArticles();	// Reset articles array to consider form articles order

		$previouslyUsedTags = $howto->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(HowtoType::class, $howto);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($howto);

			$embaddableUtils = $this->get(EmbeddableUtils::NAME);
			$embaddableUtils->resetSticker($howto);

			if ($howto->getUser()->getId() == $this->getUser()->getId()) {
				$howto->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($howto, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.form.alert.update_success', array( '%title%' => $howto->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(HowtoType::class, $howto);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'howto'        => $howto,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($howto),
		);
	}

	/**
	 * @Route("/pas-a-pas/{id}/delete", requirements={"id" = "\d+"}, name="core_howto_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !($howto->getIsDraft() === true && $howto->getUser()->getId() == $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_howto_delete)');
		}

		// Delete
		$howtoManager = $this->get(HowtoManager::NAME);
		$howtoManager->delete($howto);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.form.alert.delete_success', array( '%title%' => $howto->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_list'));
    }

	/**
	 * @Route("/pas-a-pas/{id}/sticker.png", requirements={"id" = "\d+"}, name="core_howto_sticker")
	 */
	public function stickerAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$id = intval($id);

		$howto = $howtoRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}
		if ($howto->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_howto_sticker)');
		}

		$sticker = $howto->getSticker();
		if (is_null($sticker)) {
			$embeddableUtils = $this->get(EmbeddableUtils::NAME);
			$sticker = $embeddableUtils->generateSticker($howto);
			if (!is_null($sticker)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating sticker (core_howto_sticker)');
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
	 * @Route("/pas-a-pas/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_howto_list_filter")
	 * @Route("/pas-a-pas/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_list_filter_page")
	 * @Template()
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/pas-a-pas/", name="core_howto_list")
	 * @Route("/pas-a-pas/{page}", requirements={"page" = "\d+"}, name="core_howto_list_page")
	 * @Template()
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$layout = $request->get('layout', 'view');

		$routeParameters = array();
		if ($layout != 'view') {
			$routeParameters['layout'] = $layout;
		}

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

					case 'author':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'user.displayname', 'user.fullname', 'user.username'  ));
						$filters[] = $filter;

						break;

					case 'license':

						$filter = new \Elastica\Query\MatchPhrase('license.strippedname', $facet->value);
						$filters[] = $filter;

						break;

					case 'content-creations':

						$filter = new \Elastica\Query\Range('creationCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-plans':

						$filter = new \Elastica\Query\Range('planCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-workshops':

						$filter = new \Elastica\Query\Range('workshopCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-providers':

						$filter = new \Elastica\Query\Range('providerCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-videos':

						$filter = new \Elastica\Query\Range('bodyBlockVideoCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'wip':

						$filter = new \Elastica\Query\Range('isWorkInProgress', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'sort':

						switch ($facet->value) {

							case 'recent':
								$sort = array( 'changedAt' => array( 'order' => 'desc' ) );
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
							$filter->setFields(array( 'title', 'body', 'articles.title', 'articles.body', 'tags.name' ));
							$filters[] = $filter;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			'fos_elastica.index.ladb.howto_howto',
			\Ladb\CoreBundle\Entity\Howto\Howto::CLASS_NAME,
			'core_howto_list_page',
			$routeParameters
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities']));

		$parameters = array_merge($searchParameters, array(
			'howtos'          => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters,
		));

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('LadbCoreBundle:Howto/Howto:list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('LadbCoreBundle:Howto/Howto:list-xhr.html.twig', $parameters);
			}
		}

		if ($layout == 'choice') {
			return $this->render('LadbCoreBundle:Howto/Howto:list-choice.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getDraftHowtoCount() > 0) {

			$draftPath = $this->generateUrl('core_user_show_howtos_filter', array( 'username' => $this->getUser()->getUsernameCanonical(), 'filter' => 'draft' ));
			$draftCount = $this->getUser()->getDraftHowtoCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->transchoice('howto.choice.draft_alert', $draftCount, array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/pas-a-pas/{id}/creations", requirements={"id" = "\d+"}, name="core_howto_creations")
	 * @Route("/pas-a-pas/{id}/creations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_creations_filter")
	 * @Route("/pas-a-pas/{id}/creations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_creations_filter_page")
	 * @Template()
	 */
	public function creationsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}

		// Creations

		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_creations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'creations'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
    }

	/**
	 * @Route("/pas-a-pas/{id}/ateliers", requirements={"id" = "\d+"}, name="core_howto_workshops")
	 * @Route("/pas-a-pas/{id}/ateliers/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_workshops_filter")
	 * @Route("/pas-a-pas/{id}/ateliers/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_workshops_filter_page")
	 * @Template()
	 */
	public function workshopsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}

		// Workshops

		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workshopRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_workshops_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workshops'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Workshop:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
	}

	/**
	 * @Route("/pas-a-pas/{id}/plans", requirements={"id" = "\d+"}, name="core_howto_plans")
	 * @Route("/pas-a-pas/{id}/plans/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_plans_filter")
	 * @Route("/pas-a-pas/{id}/plans/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_plans_filter_page")
	 * @Template()
	 */
	public function plansAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}

		// Plans

		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_plans_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'plans'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
	}

	/**
	 * @Route("/pas-a-pas/{id}/fournisseurs", requirements={"id" = "\d+"}, name="core_howto_providers")
	 * @Route("/pas-a-pas/{id}/fournisseurs/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_providers_filter")
	 * @Route("/pas-a-pas/{id}/fournisseurs/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_providers_filter_page")
	 * @Template()
	 */
	public function providersAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$howto = $howtoRepository->findOneById($id);
		if (is_null($howto)) {
			throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
		}

		// Providers

		$providerRepository = $om->getRepository(Provider::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $providerRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_providers_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'providers'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Howto/Howto:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
	}

	/**
	 * @Route("/pas-a-pas/{id}.html", name="core_howto_show")
	 * @Template()
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$howto = $howtoRepository->findOneByIdJoinedOnOptimized($id);
        if (is_null($howto)) {
			if ($response = $witnessManager->checkResponse(Howto::TYPE, $id)) {
				return $response;
			}
            throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
        }
		if ($howto->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $howto->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Howto::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_howto_show)');
			}
		}

		$howtoUtils = $this->get(HowtoUtils::NAME);
		$embaddableUtils = $this->get(EmbeddableUtils::NAME);
		$referral = $embaddableUtils->processReferer($howto, $request);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($howto));

		return $howtoUtils->computeShowParameters($howto, $referral);
	}

	// Backward compatibilities /////

	/**
	 * @Route("/projets/article/{id}.html", name="core_project_article_show")
	 */
	public function projectShowArticleAction($id) {
		return $this->redirect($this->generateUrl('core_howto_article_show', array( 'id' => $id )) );
	}

	/**
	 * @Route("/projets/", name="core_project_list")
	 */
	public function projectListAction() {
		return $this->redirect($this->generateUrl('core_howto_list') );
	}

	/**
	 * @Route("/projets/{id}/plans", name="core_project_plans")
	 */
	public function projectPlansAction($id) {
		return $this->redirect($this->generateUrl('core_howto_plans', array( 'id' => $id )) );
	}

	/**
	 * @Route("/projets/{id}/creations", name="core_project_creations")
	 */
	public function projectCreationsAction($id) {
		return $this->redirect($this->generateUrl('core_howto_creations', array( 'id' => $id )) );
	}

	/**
	 * @Route("/projets/{id}/ateliers", name="core_project_workshops")
	 */
	public function projectWorkshopsAction($id) {
		return $this->redirect($this->generateUrl('core_howto_workshops', array( 'id' => $id )) );
	}

	/**
	 * @Route("/projets/{id}.html", name="core_project_show")
	 */
	public function projectShowAction($id) {
		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $id )) );
	}

}