<?php

namespace App\Controller\Wonder;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Howto\Howto;
use App\Entity\Wonder\Plan;
use App\Entity\Wonder\Workshop;
use App\Entity\Workflow\Workflow;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Form\Type\Wonder\WorkshopType;
use App\Manager\Core\WitnessManager;
use App\Manager\Wonder\WorkshopManager;
use App\Model\HiddableInterface;
use App\Utils\BlockBodiedUtils;
use App\Utils\CollectionnableUtils;
use App\Utils\CommentableUtils;
use App\Utils\EmbeddableUtils;
use App\Utils\ExplorableUtils;
use App\Utils\FeedbackableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\FollowerUtils;
use App\Utils\LikableUtils;
use App\Utils\LocalisableUtils;
use App\Utils\PaginatorUtils;
use App\Utils\PicturedUtils;
use App\Utils\SearchUtils;
use App\Utils\StripableUtils;
use App\Utils\TagUtils;
use App\Utils\WatchableUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/ateliers")
 */
class WorkshopController extends AbstractController {

	use PublicationControllerTrait;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.WitnessManager::class,
            '?'.WorkshopManager::class,
            '?'.BlockBodiedUtils::class,
            '?'.CollectionnableUtils::class,
            '?'.CommentableUtils::class,
            '?'.EmbeddableUtils::class,
            '?'.ExplorableUtils::class,
            '?'.FeedbackableUtils::class,
            '?'.FieldPreprocessorUtils::class,
            '?'.FollowerUtils::class,
            '?'.LikableUtils::class,
            '?'.LocalisableUtils::class,
            '?'.PaginatorUtils::class,
            '?'.PicturedUtils::class,
            '?'.SearchUtils::class,
            '?'.StripableUtils::class,
            '?'.TagUtils::class,
            '?'.WatchableUtils::class,
        ));
    }

	/**
	 * @Route("/new", name="core_workshop_new")
	 * @Template("Wonder/Workshop/new.html.twig")
	 */
	public function new(Request $request) {

		$workshop = new Workshop();
		$workshop->addBodyBlock(new \App\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(WorkshopType::class, $workshop);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'owner'        => $this->retrieveOwner($request),
			'tagProposals' => $tagUtils->getProposals($workshop),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_workshop_create")
	 * @Template("Wonder/Workshop/new.html.twig")
	 */
	public function create(Request $request) {

		$owner = $this->retrieveOwner($request);

		$this->createLock('core_workshop_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$workshop = new Workshop();
		$form = $this->createForm(WorkshopType::class, $workshop);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($workshop);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($workshop);

			$workshop->setUser($owner);
			$workshop->setMainPicture($workshop->getPictures()->first());
			$owner->getMeta()->incrementPrivateWorkshopCount();

			$om->persist($workshop);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($workshop), PublicationListener::PUBLICATION_CREATED);

			return $this->redirect($this->generateUrl('core_workshop_show', array('id' => $workshop->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'workshop'     => $workshop,
			'form'         => $form->createView(),
			'owner'        => $owner,
			'tagProposals' => $tagUtils->getProposals($workshop),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_workshop_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_workshop_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertLockUnlockable($workshop, $lock);

		// Lock or Unlock
		$workshopManager = $this->get(WorkshopManager::class);
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
	public function publish($id) {

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertPublishable($workshop);

		// Publish
		$workshopManager = $this->get(WorkshopManager::class);
		$workshopManager->publish($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.form.alert.publish_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_workshop_show', array( 'id' => $workshop->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_workshop_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertUnpublishable($workshop);

		// Unpublish
		$workshopManager = $this->get(WorkshopManager::class);
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
	 * @Template("Wonder/Workshop/edit.html.twig")
	 */
	public function edit($id) {

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertEditabable($workshop);

		$form = $this->createForm(WorkshopType::class, $workshop);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'workshop'     => $workshop,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_workshop_update")
	 * @Template("Wonder/Workshop/edit.html.twig")
	 */
	public function update(Request $request, $id) {

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertEditabable($workshop);

		$originalBodyBlocks = $workshop->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $workshop->getTags()->toArray();	// Need to be an array to copy values

		$picturedUtils = $this->get(PicturedUtils::class);
		$picturedUtils->resetPictures($workshop); // Reset pictures array to consider form pictures order

		$workshop->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(WorkshopType::class, $workshop);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($workshop, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($workshop);

			$embaddableUtils = $this->get(EmbeddableUtils::class);
			$embaddableUtils->resetSticker($workshop);

			$stripableUtils = $this->get(StripableUtils::class);
			$stripableUtils->resetStrip($workshop);

			$workshop->setMainPicture($workshop->getPictures()->first());
			if ($workshop->getUser() == $this->getUser()) {
				$workshop->setUpdatedAt(new \DateTime());
			}

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($workshop, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.form.alert.update_success', array( '%title%' => $workshop->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(WorkshopType::class, $workshop);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'workshop'     => $workshop,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_workshop_delete")
	 */
	public function delete($id) {

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertDeletable($workshop);

		// Delete
		$workshopManager = $this->get(WorkshopManager::class);
		$workshopManager->delete($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.form.alert.delete_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_workshop_list'));
	}

	/**
	 * @Route("/{id}/chown", requirements={"id" = "\d+"}, name="core_workshop_chown")
	 */
	public function chown(Request $request, $id) {

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertChownable($workshop);

		$targetUser = $this->retrieveOwner($request);

		// Change owner
		$workshopManager = $this->get(WorkshopManager::class);
		$workshopManager->changeOwner($workshop, $targetUser);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.form.alert.chown_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_workshop_show', array( 'id' => $workshop->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/plans", requirements={"id" = "\d+"}, name="core_workshop_plans")
	 * @Route("/{id}/plans/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workshop_plans_filter")
	 * @Route("/{id}/plans/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_plans_filter_page")
	 * @Template("Wonder/Workshop/plans.html.twig")
	 */
	public function plans(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertShowable($workshop);

		// Plans

		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

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
			return $this->render('Wonder/Plan/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workshop' => $workshop,
		));
	}

	/**
	 * @Route("/{id}/projets", requirements={"id" = "\d+"}, name="core_workshop_projects")
	 */
	public function projects($id) {
		return $this->redirect($this->generateUrl('core_workshop_howtos', array( 'id' => $id )));
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_workshop_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workshop_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_howtos_filter_page")
	 * @Template("Wonder/Workshop/howtos.html.twig")
	 */
	public function howtos(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertShowable($workshop);

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

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
			return $this->render('Wonder/Plan/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workshop' => $workshop,
		));
	}

	/**
	 * @Route("/{id}/processus", requirements={"id" = "\d+"}, name="core_workshop_workflows")
	 * @Route("/{id}/processus/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workshop_workflows_filter")
	 * @Route("/{id}/processus/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_workflows_filter_page")
	 * @Template("Wonder/Workshop/workflows.html.twig")
	 */
	public function workflows(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertShowable($workshop);

		// Howtos

		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

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
			return $this->render('Wonder/Plan/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workshop' => $workshop,
		));
	}

	/**
	 * @Route("/{id}/sticker.png", requirements={"id" = "\d+"}, name="core_workshop_sticker_bc")
	 */
	public function bcSticker(Request $request, $id) {
		return $this->redirect($this->generateUrl('core_workshop_sticker', array( 'id' => $id )));
	}

	/**
	 * @Route("/{id}/sticker", requirements={"id" = "\d+"}, name="core_workshop_sticker")
	 */
	public function sticker(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertShowable($workshop, true);

		$sticker = $workshop->getSticker();
		if (is_null($sticker)) {
			$embeddableUtils = $this->get(EmbeddableUtils::class);
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
	public function strip(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertShowable($workshop, true);

		$strip = $workshop->getStrip();
		if (is_null($strip)) {
			$stripableUtils = $this->get(StripableUtils::class);
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
	 * @Template("Wonder/Workshop/widget-xhr.html.twig")
	 */
	public function widget($id) {

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertShowable($workshop, true);

		return array(
			'workshop' => $workshop,
		);
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_workshop_location", defaults={"_format" = "json"})
	 * @Template("Wonder/Workshop/location.geojson.twig")
	 */
	public function location($id) {

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertShowable($workshop);

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
	 * @Route("/{id}/card.xhr", name="core_workshop_card")
	 * @Template("Wonder/Workshop/card-xhr.html.twig")
	 */
	public function card(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_workshop_card)');
		}

		$workshop = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertShowable($workshop);

		return array(
			'workshop' => $workshop,
		);
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_workshop_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_list_filter_page")
	 */
	public function goneList(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_workshop_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_workshop_list_page")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_workshop_list_geojson")
	 * @Template("Wonder/Workshop/list.html.twig")
	 */
	public function list(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::class);

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

						$localisableUtils = $this->get(LocalisableUtils::class);
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
			function(&$filters) {

				$this->pushGlobalVisibilityFilter($filters, true, true);

			},
			'wonder_workshop',
			\App\Entity\Wonder\Workshop::CLASS_NAME,
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

			return $this->render('Wonder/Workshop/list-xhr.geojson.twig', $parameters);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Workshop/list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateWorkshopCount() > 0) {

			$draftPath = $this->generateUrl('core_workshop_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateWorkshopCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->trans('wonder.workshop.choice.draft_alert', array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_workshop_show")
	 * @Template("Wonder/Workshop/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			if ($response = $witnessManager->checkResponse(Workshop::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		$this->assertShowable($workshop);

		$embaddableUtils = $this->get(EmbeddableUtils::class);
		$referral = $embaddableUtils->processReferer($workshop, $request);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($workshop), PublicationListener::PUBLICATION_SHOWN);

		$explorableUtils = $this->get(ExplorableUtils::class);
		$userWorkshops = $explorableUtils->getPreviousAndNextPublishedUserExplorables($workshop, $workshopRepository, $workshop->getUser()->getMeta()->getPublicWorkshopCount());
		$similarWorkshops = $explorableUtils->getSimilarExplorables($workshop, 'wonder_workshop', Workshop::CLASS_NAME, $userWorkshops);

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$feedbackableUtils = $this->get(FeedbackableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
		$followerUtils = $this->get(FollowerUtils::class);

		return array(
			'workshop'          => $workshop,
			'permissionContext' => $this->getPermissionContext($workshop),
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
	 * @Route("/{id}/admin/converttocreation", requirements={"id" = "\d+"}, name="core_workshop_admin_converttocreation")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_workshop_admin_converttocreation)")
	 */
	public function adminConvertToCreation($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneById($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Convert
		$workshopManager = $this->get(WorkshopManager::class);
		$creation = $workshopManager->convertToCreation($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.admin.alert.converttocreation_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_creation_show', array( 'id' => $creation->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/admin/converttohowto", requirements={"id" = "\d+"}, name="core_workshop_admin_converttohowto")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_workshop_admin_converttohowto)")
	 */
	public function adminConvertToHowto($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneById($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Convert
		$workshopManager = $this->get(WorkshopManager::class);
		$howto = $workshopManager->convertToHowto($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.workshop.admin.alert.converttohowto_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

}
