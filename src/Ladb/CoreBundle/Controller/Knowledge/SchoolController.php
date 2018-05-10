<?php

namespace Ladb\CoreBundle\Controller\Knowledge;

use Ladb\CoreBundle\Utils\LocalisableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Manager\Knowledge\SchoolManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Form\Type\Knowledge\NewSchoolType;
use Ladb\CoreBundle\Form\Model\NewSchool;
use Ladb\CoreBundle\Entity\Knowledge\School;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\PropertyUtils;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\KnowledgeEvent;
use Ladb\CoreBundle\Event\KnowledgeListener;

/**
 * @Route("/ecoles")
 */
class SchoolController extends Controller {

	/**
	 * @Route("/new", name="core_school_new")
	 * @Template("LadbCoreBundle:Knowledge/School:new.html.twig")
	 */
	public function newAction() {

		$newSchool = new NewSchool();
		$form = $this->createForm(NewSchoolType::class, $newSchool);

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/create", name="core_school_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Knowledge/School:new.html.twig")
	 */
	public function createAction(Request $request) {
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
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($school, array( 'field' => School::FIELD_NAME, 'value' => $nameValue )));
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($school, array( 'field' => School::FIELD_LOGO, 'value' => $logoValue )));

			$nameValue->setParentEntity($school);
			$nameValue->setParentEntityField(School::FIELD_NAME);
			$nameValue->setUser($user);

			$logoValue->setParentEntity($school);
			$logoValue->setParentEntityField(School::FIELD_LOGO);
			$logoValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Name and Logo of this new school

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createContributeActivity($nameValue, false);
			$activityUtils->createContributeActivity($logoValue, false);

			// Dispatch publication event
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($school));

			$om->flush();

			// Dispatch publication event
			$dispatcher->dispatch(PublicationListener::PUBLICATION_PUBLISHED, new PublicationEvent($school));

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
	 * @Security("has_role('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_school_delete)")
	 */
	public function deleteAction($id) {
		$propertyUtils = $this->get(PropertyUtils::NAME);
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$school = $schoolRepository->findOneById($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		// Delete
		$schoolManager = $this->get(SchoolManager::NAME);
		$schoolManager->delete($school);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.school.form.alert.delete_success', array( '%title%' => $school->getTitle() )));

		return $this->redirect($this->generateUrl('core_school_list'));
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_school_location", defaults={"_format" = "json"})
	 * @Template("LadbCoreBundle:Knowledge/School:location.geojson.twig")
	 */
	public function locationAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$id = intval($id);

		$school = $schoolRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		$features = array();
		if (!is_null($school->getLongitude()) && !is_null($school->getLatitude())) {
			$properties = array(
				'type' => 0,
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
	 * @Route("/", name="core_school_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_school_list_page")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_school_list_geojson")
	 * @Template("LadbCoreBundle:Knowledge/School:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::NAME);

		$layout = $request->get('layout', 'view');

		$routeParameters = array();
		if ($layout != 'view') {
			$routeParameters['layout'] = $layout;
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {

				switch ($facet->name) {

					// Filters /////

					case 'geocoded':

						$filter = new \Elastica\Query\Exists('geoPoint');
						$filters[] = $filter;

						break;

					case 'location':

						$localisableUtils = $this->get(LocalisableUtils::NAME);
						$bounds = $localisableUtils->getTopLeftBottomRightBounds($facet->value);

						if (!is_null($bounds)) {
							$filter = new \Elastica\Query\GeoBoundingBox('geoPoint', $bounds);
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

					case 'rejected':

						$filter = new \Elastica\Query\Range('nameRejected', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'changedAt' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-views':
						$sort = array( 'viewCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'likeCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-comments':
						$sort = array( 'commentCount' => array( 'order' => 'desc' ) );
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

						}

				}
			},
			function(&$filters, &$sort) {

				$filters[] = new \Elastica\Query\Range('nameRejected', array( 'lt' => 1 ));

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			null,
			'fos_elastica.index.ladb.knowledge_school',
			\Ladb\CoreBundle\Entity\Knowledge\School::CLASS_NAME,
			'core_school_list_page',
			$routeParameters
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities']));

		$parameters = array_merge($searchParameters, array(
			'schools'       => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters,
		));

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $school) {
				$properties = array(
					'type'    => 0,
					'cardUrl' => $this->generateUrl('core_school_card', array( 'id' => $school->getId() )),
				);
				$gerometry = new \GeoJson\Geometry\Point($school->getGeoPoint());
				$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
			}
			$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
			$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

			$parameters = array_merge($parameters, array(
				'collection' => $collection,
			));

			return $this->render('LadbCoreBundle:Knowledge/School:list-xhr.geojson.twig', $parameters);
		}

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('LadbCoreBundle:Knowledge/School:list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('LadbCoreBundle:Knowledge/School:list-xhr.html.twig', $parameters);
			}
		}

		if ($layout == 'choice') {
			return $this->render('LadbCoreBundle:Knowledge/School:list-choice.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}/creations", requirements={"id" = "\d+"}, name="core_school_creations")
	 * @Route("/{id}/creations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_school_creations_filter")
	 * @Route("/{id}/creations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_school_creations_filter_page")
	 * @Template("LadbCoreBundle:Knowledge/School:creations.html.twig")
	 */
	public function creationsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$school = $schoolRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		// Creations

		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'school' => $school,
		));
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_school_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_school_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_school_howtos_filter_page")
	 * @Template("LadbCoreBundle:Knowledge/School:howtos.html.twig")
	 */
	public function howtosAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);

		$school = $schoolRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($school)) {
			throw $this->createNotFoundException('Unable to find School entity (id='.$id.').');
		}

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $howtoRepository->findPaginedBySchool($school, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_howtos_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'howtos'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'school' => $school,
		));
	}

	/**
	 * @Route("/{id}/card.xhr", name="core_school_card")
	 * @Template("LadbCoreBundle:Knowledge/School:card-xhr.html.twig")
	 */
	public function cardAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
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
	 * @Template("LadbCoreBundle:Knowledge/School:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$schoolRepository = $om->getRepository(School::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$school = $schoolRepository->findOneByIdJoinedOnOptimized($id);
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
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($school));

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);

		return array(
			'school'          => $school,
			'likeContext'     => $likableUtils->getLikeContext($school, $this->getUser()),
			'watchContext'    => $watchableUtils->getWatchContext($school, $this->getUser()),
			'commentContext'  => $commentableUtils->getCommentContext($school),
			'hasMap'          => !is_null($school->getLatitude()) && !is_null($school->getLongitude()),
			'userTestimonial' => $userTestimonial,
		);
	}

}
