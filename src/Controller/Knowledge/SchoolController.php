<?php

namespace App\Controller\Knowledge;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Knowledge\Provider;
use App\Utils\KnowledgeUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Wonder\Plan;
use App\Entity\Howto\Howto;
use App\Entity\Wonder\Creation;
use App\Entity\Knowledge\School;
use App\Manager\Knowledge\SchoolManager;
use App\Manager\Core\WitnessManager;
use App\Utils\LocalisableUtils;
use App\Utils\ActivityUtils;
use App\Utils\PaginatorUtils;
use App\Utils\CommentableUtils;
use App\Utils\LikableUtils;
use App\Utils\WatchableUtils;
use App\Utils\SearchUtils;
use App\Utils\PropertyUtils;
use App\Utils\CollectionnableUtils;
use App\Form\Type\Knowledge\NewSchoolType;
use App\Form\Model\NewSchool;
use App\Event\PublicationsEvent;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\KnowledgeEvent;
use App\Event\KnowledgeListener;

/**
 * @Route("/ecoles")
 */
class SchoolController extends AbstractController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_school_new")
	 * @Template("Knowledge/School/new.html.twig")
	 */
	public function new() {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_school_new)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::class);

		$newSchool = new NewSchool();
		$form = $this->createForm(NewSchoolType::class, $newSchool);

		return array(
			'form' => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_school_create")
	 * @Template("Knowledge/School/new.html.twig")
	 */
	public function create(Request $request) {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_school_new)');
		}

		$this->createLock('core_school_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$dispatcher = $this->get('event_dispatcher');

		$newSchool = new NewSchool();
		$form = $this->createForm(NewSchoolType::class, $newSchool);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$nameValue = $newSchool->getNameValue();
			$logoValue = $newSchool->getLogoValue();
			$user = $this->getUser();

			$school = new School();
			$school->setName($nameValue->getData());
			$school->incrementContributorCount();

			$om->persist($school);
			$om->flush();	// Need to save school to be sure ID is generated

			$school->addNameValue($nameValue);
			$school->addLogoValue($logoValue);

			// Dispatch knowledge events
			$dispatcher->dispatch(new KnowledgeEvent($school, array( 'field' => School::FIELD_NAME, 'value' => $nameValue )), KnowledgeListener::FIELD_VALUE_ADDED);
			$dispatcher->dispatch(new KnowledgeEvent($school, array( 'field' => School::FIELD_LOGO, 'value' => $logoValue )), KnowledgeListener::FIELD_VALUE_ADDED);

			$nameValue->setParentEntity($school);
			$nameValue->setParentEntityField(School::FIELD_NAME);
			$nameValue->setUser($user);

			$logoValue->setParentEntity($school);
			$logoValue->setParentEntityField(School::FIELD_LOGO);
			$logoValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Name and Logo of this new school

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createContributeActivity($nameValue, false);
			$activityUtils->createContributeActivity($logoValue, false);

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($school), PublicationListener::PUBLICATION_CREATED);

			$om->flush();

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($school), PublicationListener::PUBLICATION_PUBLISHED);

			return $this->redirect($this->generateUrl('core_school_show', array('id' => $school->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		return array(
			'newSchool' => $newSchool,
			'form'        => $form->createView(),
			'hideWarning' => true,
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_school_delete")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_school_delete)")
	 */
	public function delete($id) {

		$school = $this->retrievePublication($id, School::CLASS_NAME);
		$this->assertDeletable($school);

		// Delete
		$schoolManager = $this->get(SchoolManager::class);
		$schoolManager->delete($school);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.school.form.alert.delete_success', array( '%title%' => $school->getTitle() )));

		return $this->redirect($this->generateUrl('core_school_list'));
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_school_location", defaults={"_format" = "json"})
	 * @Template("Knowledge/School/location.geojson.twig")
	 */
	public function location(Request $request, $id) {

		$school = $this->retrievePublication($id, School::CLASS_NAME);
		$this->assertShowable($school);

		$features = array();
		if (!is_null($school->getLongitude()) && !is_null($school->getLatitude())) {
			$properties = array(
				'color'   => 'green',
				'cardUrl' => $this->generateUrl('core_school_card', array('id' => $school->getId())),
			);
			$gerometry = new \GeoJson\Geometry\Point($school->getGeoPoint());
			$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
		}

		$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
		$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

		return array(
			'collection' => $collection,
		);
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_school_widget")
	 * @Template("Knowledge/School/widget-xhr.html.twig")
	 */
	public function widget(Request $request, $id) {

		$school = $this->retrievePublication($id, School::CLASS_NAME);
		$this->assertShowable($school, true);

		return array(
			'school' => $school,
		);
	}

	/**
	 * @Route("/", name="core_school_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_school_list_page")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_school_list_geojson")
	 * @Template("Knowledge/School/list.html.twig")
	 */
	public function list(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_school_list_page)');
		}

		$layout = $request->get('layout', 'view');

		$routeParameters = array();
		if ($layout != 'view') {
			$routeParameters['layout'] = $layout;
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {

				switch ($facet->name) {

					// Filters /////

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

					case 'around':

						if (isset($facet->value)) {
							$filter = new \Elastica\Query\GeoDistance('geoPoint', $facet->value, '100km');
							$filters[] = $filter;
						}

						break;

					case 'diplomas':

						$filter = new \Elastica\Query\MatchPhrase('diplomas', $facet->value);
						$filters[] = $filter;

						break;

					case 'training-types':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'trainingTypes' ));
						$filters[] = $filter;

						break;

					case 'with-diploma':

						$filter = new \Elastica\Query\Exists('diplomas');
						$filters[] = $filter;

						break;

					case 'with-testimonial':

						$filter = new \Elastica\Query\Range('testimonialCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-creations':

						$filter = new \Elastica\Query\Range('creationCount', array( 'gt' => 0 ));
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

					case 'rejected':

						$filter = new \Elastica\Query\Range('nameRejected', array( 'gte' => 1 ));
						$filters[] = $filter;

						$noGlobalFilters = true;

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

					case 'sort-completion':
						$sort = array( 'completion100' => array( 'order' =>  $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'name^100', 'geographicalAreas^50', 'description' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) {

				$filters[] = new \Elastica\Query\Range('nameRejected', array( 'lt' => true ));

			},
			'knowledge_school',
			\App\Entity\Knowledge\School::CLASS_NAME,
			'core_school_list_page',
			$routeParameters
		);

		$parameters = array_merge($searchParameters, array(
			'schools'         => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters,
		));

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $school) {
				$properties = array(
					'color'   => 'green',
					'cardUrl' => $this->generateUrl('core_school_card', array('id' => $school->getId())),
				);
				$gerometry = new \GeoJson\Geometry\Point($school->getGeoPoint());
				$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
			}
			$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
			$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

			$parameters = array_merge($parameters, array(
				'collection' => $collection,
			));

			return $this->render('Knowledge/School/list-xhr.geojson.twig', $parameters);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('Knowledge/School/list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('Knowledge/School/list-xhr.html.twig', $parameters);
			}
		}

		if ($layout == 'choice') {
			return $this->render('Knowledge/School/list-choice.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}/creations", requirements={"id" = "\d+"}, name="core_school_creations")
	 * @Route("/{id}/creations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_school_creations_filter")
	 * @Route("/{id}/creations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_school_creations_filter_page")
	 * @Template("Knowledge/School/creations.html.twig")
	 */
	public function creations(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$school = $schoolRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		// Creations

		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedBySchool($school, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_school_creations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'creations'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Creation/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'school' => $school,
		));
	}

	/**
	 * @Route("/{id}/plans", requirements={"id" = "\d+"}, name="core_school_plans")
	 * @Route("/{id}/plans/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_school_plans_filter")
	 * @Route("/{id}/plans/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_school_plans_filter_page")
	 * @Template("Knowledge/School/plans.html.twig")
	 */
	public function plans(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$school = $schoolRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		// Plans

		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedBySchool($school, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_school_plans_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

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
			'school' => $school,
		));
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_school_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_school_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_school_howtos_filter_page")
	 * @Template("Knowledge/School/howtos.html.twig")
	 */
	public function howtos(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$school = $schoolRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $howtoRepository->findPaginedBySchool($school, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_school_howtos_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'howtos'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Howto/Howto/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'school' => $school,
		));
	}

	/**
	 * @Route("/{id}/card.xhr", name="core_school_card")
	 * @Template("Knowledge/School/card-xhr.html.twig")
	 */
	public function card(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_school_card)');
		}

		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$id = intval($id);

		$school = $schoolRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		return array(
			'school' => $school,
		);
	}

	/**
	 * @Route("/{id}.html", name="core_school_show")
	 * @Template("Knowledge/School/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$school = $schoolRepository->findOneById($id);
		if (is_null($school)) {
			if ($response = $witnessManager->checkResponse(School::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		$user = $this->getUser();
		$userTestimonial = null;
		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
			foreach ($school->getTestimonials() as $testimonial) {
				if ($testimonial->getUser()->getId() == $user->getId()) {
					$userTestimonial = $testimonial;
					break;
				}
			}
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($school), PublicationListener::PUBLICATION_SHOWN);

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);

		return array(
			'school'            => $school,
			'permissionContext' => $this->getPermissionContext($school),
			'likeContext'       => $likableUtils->getLikeContext($school, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($school, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($school),
			'collectionContext' => $collectionnableUtils->getCollectionContext($school),
			'hasMap'            => !is_null($school->getLatitude()) && !is_null($school->getLongitude()),
			'userTestimonial'   => $userTestimonial,
		);
	}

}
