<?php

namespace Ladb\CoreBundle\Controller\Wonder;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\FeedbackableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Wonder\Workshop;
use Ladb\CoreBundle\Form\Type\Wonder\WorkshopType;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Utils\EmbeddableUtils;
use Ladb\CoreBundle\Utils\StripableUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Manager\Wonder\WorkshopManager;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\LocalisableUtils;

/**
 * @Route("/ateliers")
 */
class WorkshopController extends AbstractController {

	/**
	 * @Route("/new", name="core_workshop_new")
	 * @Template("LadbCoreBundle:Wonder/Workshop:new.html.twig")
	 */
	public function newAction() {

		$workshop = new Workshop();
		$workshop->addBodyBlock(new \Ladb\CoreBundle\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(WorkshopType::class, $workshop);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_workshop_create")
	 * @Template("LadbCoreBundle:Wonder/Workshop:new.html.twig")
	 */
	public function createAction(Request $request) {

		$this->createLock('core_workshop_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$workshop = new Workshop();
		$form = $this->createForm(WorkshopType::class, $workshop);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($workshop);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($workshop);

			$workshop->setUser($this->getUser());
			$workshop->setMainPicture($workshop->getPictures()->first());
			$this->getUser()->getMeta()->incrementPrivateWorkshopCount();

			$om->persist($workshop);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($workshop));

			return $this->redirect($this->generateUrl('core_workshop_show', array('id' => $workshop->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'workshop'     => $workshop,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_workshop_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_workshop_unlock")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_workshop_lock or core_workshop_unlock)")
	 */
	public function lockUnlockAction($id, $lock) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneById($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_workshop_lock or core_workshop_unlock)');
		}

		// Lock or Unlock
		$workshopManager = $this->get(WorkshopManager::NAME);
		if ($lock) {
			$workshopManager->lock($workshop);
		} else {
			$workshopManager->unlock($workshop);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_workshop_show', array( 'id' => $workshop->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_workshop_publish")
	 */
	public function publishAction($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workshop->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_workshop_publish)');
		}
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not emailConfirmed (core_workshop_publish)');
		}
		if ($workshop->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_workshop_publish)');
		}
		if ($workshop->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked (core_workshop_publish)');
		}

		// Publish
		$workshopManager = $this->get(WorkshopManager::NAME);
		$workshopManager->publish($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.form.alert.publish_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_workshop_show', array( 'id' => $workshop->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_workshop_unpublish")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_workshop_unpublish)")
	 */
	public function unpublishAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_workshop_unpublish)');
		}

		// Unpublish
		$workshopManager = $this->get(WorkshopManager::NAME);
		$workshopManager->unpublish($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.form.alert.unpublish_success', array( '%title%' => $workshop->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_workshop_edit")
	 * @Template("LadbCoreBundle:Wonder/Workshop:edit.html.twig")
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workshop->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_workshop_edit)');
		}

		$form = $this->createForm(WorkshopType::class, $workshop);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'workshop'     => $workshop,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_workshop_update")
	 * @Template("LadbCoreBundle:Wonder/Workshop:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workshop->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_workshop_update)');
		}

		$originalBodyBlocks = $workshop->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $workshop->getTags()->toArray();	// Need to be an array to copy values

		$picturedUtils = $this->get(PicturedUtils::NAME);
		$picturedUtils->resetPictures($workshop); // Reset pictures array to consider form pictures order

		$workshop->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(WorkshopType::class, $workshop);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($workshop, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($workshop);

			$embaddableUtils = $this->get(EmbeddableUtils::NAME);
			$embaddableUtils->resetSticker($workshop);

			$stripableUtils = $this->get(StripableUtils::NAME);
			$stripableUtils->resetStrip($workshop);

			$workshop->setMainPicture($workshop->getPictures()->first());
			if ($workshop->getUser()->getId() == $this->getUser()->getId()) {
				$workshop->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($workshop, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.form.alert.update_success', array( '%title%' => $workshop->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(WorkshopType::class, $workshop);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'workshop'     => $workshop,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_workshop_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !($workshop->getIsDraft() === true && $workshop->getUser()->getId() == $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_workshop_delete)');
		}

		// Delete
		$workshopManager = $this->get(WorkshopManager::NAME);
		$workshopManager->delete($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.form.alert.delete_success', array( '%title%' => $workshop->getTitle() )));

		if ($workshop->getIsDraft()) {
			return $this->redirect($this->generateUrl('core_user_show_workshops', array( 'username' => $this->getUser()->getUsernameCanonical() )));
		}
		return $this->redirect($this->generateUrl('core_workshop_list'));
	}

	/**
	 * @Route("/{id}/plans", requirements={"id" = "\d+"}, name="core_workshop_plans")
	 * @Route("/{id}/plans/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workshop_plans_filter")
	 * @Route("/{id}/plans/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_plans_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Workshop:plans.html.twig")
	 */
	public function plansAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Plans

		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedByWorkshop($workshop, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workshop_plans_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

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
			'workshop' => $workshop,
		));
	}

	/**
	 * @Route("/{id}/projets", requirements={"id" = "\d+"}, name="core_workshop_projects")
	 */
	public function projectsAction($id) {
		return $this->redirect($this->generateUrl('core_workshop_howtos', array( 'id' => $id )));
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_workshop_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workshop_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_howtos_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Workshop:howtos.html.twig")
	 */
	public function howtosAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $howtoRepository->findPaginedByWorkshop($workshop, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workshop_howtos_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'howtos'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workshop' => $workshop,
		));
	}

	/**
	 * @Route("/{id}/processus", requirements={"id" = "\d+"}, name="core_workshop_workflows")
	 * @Route("/{id}/processus/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workshop_workflows_filter")
	 * @Route("/{id}/processus/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_workflows_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Workshop:workflows.html.twig")
	 */
	public function workflowsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Howtos

		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workflowRepository->findPaginedByWorkshop($workshop, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workshop_workflows_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workflows'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workshop' => $workshop,
		));
	}

	/**
	 * @Route("/{id}/sticker.png", requirements={"id" = "\d+"}, name="core_workshop_sticker_bc")
	 */
	public function bcStickerAction(Request $request, $id) {
		return $this->redirect($this->generateUrl('core_workshop_sticker', array( 'id' => $id )));
	}

	/**
	 * @Route("/{id}/sticker", requirements={"id" = "\d+"}, name="core_workshop_sticker")
	 */
	public function stickerAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_workshop_sticker)');
		}

		$sticker = $workshop->getSticker();
		if (is_null($sticker)) {
			$embeddableUtils = $this->get(EmbeddableUtils::NAME);
			$sticker = $embeddableUtils->generateSticker($workshop);
			if (!is_null($sticker)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating sticker (core_workshop_sticker)');
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
	 * @Route("/{id}/strip", requirements={"id" = "\d+"}, name="core_workshop_strip")
	 */
	public function stripAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_workshop_strip)');
		}

		$strip = $workshop->getStrip();
		if (is_null($strip)) {
			$stripableUtils = $this->get(StripableUtils::NAME);
			$strip = $stripableUtils->generateStrip($workshop);
			if (!is_null($strip)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating strip (core_workshop_strip)');
			}
		}

		if (!is_null($strip)) {

			$response = $this->get('liip_imagine.controller')->filterAction($request, $strip->getWebPath(), '564w');
			return $response;

		} else {
			throw $this->createNotFoundException('No strip');
		}

	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_workshop_widget")
	 * @Template("LadbCoreBundle:Wonder/Workshop:widget-xhr.html.twig")
	 */
	public function widgetAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_workshop_widget)');
		}

		return array(
			'workshop' => $workshop,
		);
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_workshop_location", defaults={"_format" = "json"})
	 * @Template("LadbCoreBundle:Wonder/Workshop:location.geojson.twig")
	 */
	public function locationAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $workshop->getUser()->getId() != $this->getUser()->getId())) {
				throw $this->createNotFoundException('Not allowed (core_workshop_location)');
			}
		}

		$features = array();
		if (!is_null($workshop->getLongitude()) && !is_null($workshop->getLatitude())) {
			$properties = array(
				'color'   => 'orange',
				'cardUrl' => $this->generateUrl('core_workshop_card', array('id' => $workshop->getId())),
			);
			$gerometry = new \GeoJson\Geometry\Point($workshop->getGeoPoint());
			$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
		}

		$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
		$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

		return array(
			'collection' => $collection,
		);
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_workshop_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_list_filter_page")
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_workshop_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_workshop_list_page")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_workshop_list_geojson")
	 * @Template("LadbCoreBundle:Wonder/Workshop:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::NAME);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_workshop_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
				switch ($facet->name) {

					// Filters /////

					case 'mine':

						if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

							if ($facet->value == 'draft') {

								$filter = (new \Elastica\Query\BoolQuery())
									->addFilter(new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsername()))
									->addFilter(new \Elastica\Query\Range('visibility', array( 'lt' => HiddableInterface::VISIBILITY_PUBLIC )))
								;

							} else {

								$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							}

							$filters[] = $filter;

						}

						break;

					case 'period':

						if ($facet->value == 'last7days') {

							$filters[] = new \Elastica\Query\Range('changedAt', array( 'gte' => 'now-7d/d' ));

						} elseif ($facet->value == 'last30days') {

							$filters[] = new \Elastica\Query\Range('changedAt', array( 'gte' => 'now-30d/d' ));

						}

						break;

					case 'tag':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'tags.label' ));
						$filters[] = $filter;

						break;

					case 'author':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'user.displayname', 'user.fullname', 'user.username'  ));
						$filters[] = $filter;

						break;

					case 'license':

						$filter = new \Elastica\Query\Term([ 'license.strippedname' => [ 'value' => $facet->value, 'boost' => 1.0 ] ]);
						$filters[] = $filter;

						break;

					case 'content-plans':

						$filter = new \Elastica\Query\Range('planCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-howtos':

						$filter = new \Elastica\Query\Range('howtoCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-workflows':

						$filter = new \Elastica\Query\Range('workflowCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-videos':

						$filter = new \Elastica\Query\Range('bodyBlockVideoCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'with-feedback':

						$filter = new \Elastica\Query\Range('feedbackCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'area-lte':

						$filter = new \Elastica\Query\Range('area', array( 'lte' => intval($facet->value) ));
						$filters[] = $filter;

						break;

					case 'area-gte':

						$filter = new \Elastica\Query\Range('area', array( 'gte' => intval($facet->value) ));
						$filters[] = $filter;

						break;

					case 'around':

						if (isset($facet->value)) {
							$filter = new \Elastica\Query\GeoDistance('geoPoint', $facet->value, '100km');
							$filters[] = $filter;
						}

						break;

					case 'geocoded':

						$filter = new \Elastica\Query\Exists('geoPoint');
						$filters[] = $filter;

						break;

					case 'location':

						$localisableUtils = $this->get(LocalisableUtils::NAME);
						$boundsAndLocation = $localisableUtils->getBoundsAndLocation($facet->value);

						if (!is_null($boundsAndLocation)) {
							$filter = new \Elastica\Query\BoolQuery();
							if (isset($boundsAndLocation['bounds'])) {
								$geoQuery = new \Elastica\Query\GeoBoundingBox('geoPoint', $boundsAndLocation['bounds']);
								$filter->addShould($geoQuery);
							}
							if (isset($boundsAndLocation['location'])) {
								$geoQuery = new \Elastica\Query\GeoDistance('geoPoint', $boundsAndLocation['location'], '20km');
								$filter->addShould($geoQuery);
							}
							$filters[] = $filter;
						}

						break;

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'changedAt' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-views':
						$sort = array( 'viewCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'likeCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-comments':
						$sort = array( 'commentCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					case 'sort-largest':
						$sort = array( 'area' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'title^100', 'body', 'tags.label' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) use ($layout) {

				$user = $this->getUser();
				$publicVisibilityFilter = new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC ));
				if (!is_null($user) && $layout != 'choice') {

					$filter = new \Elastica\Query\BoolQuery();
					$filter->addShould(
						$publicVisibilityFilter
					);
					$filter->addShould(
						(new \Elastica\Query\BoolQuery())
							->addFilter(new \Elastica\Query\MatchPhrase('user.username', $user->getUsername()))
							->addFilter(new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PRIVATE )))
					);

				} else {
					$filter = $publicVisibilityFilter;
				}
				$filters[] = $filter;


			},
			'fos_elastica.index.ladb.wonder_workshop',
			\Ladb\CoreBundle\Entity\Wonder\Workshop::CLASS_NAME,
			'core_workshop_list_page'
		);

		$parameters = array_merge($searchParameters, array(
			'workshops' => $searchParameters['entities'],
		));

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $workshop) {
				$geoPoint = $workshop->getGeoPoint();
				if (is_null($geoPoint)) {
					continue;
				}
				$properties = array(
					'color'   => 'orange',
					'cardUrl' => $this->generateUrl('core_workshop_card', array('id' => $workshop->getId())),
				);
				$gerometry = new \GeoJson\Geometry\Point($geoPoint);
				$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
			}
			$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
			$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

			$parameters = array_merge($parameters, array(
				'collection' => $collection,
			));

			return $this->render('LadbCoreBundle:Wonder/Workshop:list-xhr.geojson.twig', $parameters);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Workshop:list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateWorkshopCount() > 0) {

			$draftPath = $this->generateUrl('core_workshop_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateWorkshopCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->transchoice('wonder.workshop.choice.draft_alert', $draftCount, array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}/card.xhr", name="core_workshop_card")
	 * @Template("LadbCoreBundle:Wonder/Workshop:card-xhr.html.twig")
	 */
	public function cardAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity.');
		}

		return array(
			'workshop' => $workshop,
		);
	}

	/**
	 * @Route("/{id}.html", name="core_workshop_show")
	 * @Template("LadbCoreBundle:Wonder/Workshop:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			if ($response = $witnessManager->checkResponse(Workshop::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $workshop->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Workshop::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_workshop_show)');
			}
		}

		$embaddableUtils = $this->get(EmbeddableUtils::NAME);
		$referral = $embaddableUtils->processReferer($workshop, $request);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($workshop));

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userWorkshops = $explorableUtils->getPreviousAndNextPublishedUserExplorables($workshop, $workshopRepository, $workshop->getUser()->getMeta()->getPublicWorkshopCount());
		$similarWorkshops = $explorableUtils->getSimilarExplorables($workshop, 'fos_elastica.index.ladb.wonder_workshop', Workshop::CLASS_NAME, $userWorkshops);

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$feedbackableUtils = $this->get(FeedbackableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'workshop'          => $workshop,
			'userWorkshops'     => $userWorkshops,
			'similarWorkshops'  => $similarWorkshops,
			'likeContext'       => $likableUtils->getLikeContext($workshop, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($workshop, $this->getUser()),
			'feedbackContext'   => $feedbackableUtils->getFeedbackContext($workshop),
			'commentContext'    => $commentableUtils->getCommentContext($workshop),
			'collectionContext' => $collectionnableUtils->getCollectionContext($workshop),
			'followerContext'   => $followerUtils->getFollowerContext($workshop->getUser(), $this->getUser()),
			'referral'          => $referral,
			'hasMap'            => !is_null($workshop->getLatitude()) && !is_null($workshop->getLongitude()),
		);
	}

	/**
	 * @Route("/{id}/admin/converttohowto", requirements={"id" = "\d+"}, name="core_workshop_admin_converttohowto")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_workshop_admin_converttohowto)")
	 */
	public function adminConvertToHowtoAction($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneById($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Convert
		$workshopManager = $this->get(WorkshopManager::NAME);
		$howto = $workshopManager->convertToHowto($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.admin.alert.converttohowto_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

}
