<?php

namespace App\Controller\Knowledge;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Utils\CollectionnableUtils;
use App\Utils\ElasticaQueryUtils;
use App\Utils\KnowledgeUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Howto\Howto;
use App\Entity\Knowledge\Provider;
use App\Entity\Wonder\Creation;
use App\Event\KnowledgeEvent;
use App\Event\KnowledgeListener;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Form\Model\NewProvider;
use App\Form\Type\Knowledge\NewProviderType;
use App\Manager\Core\WitnessManager;
use App\Manager\Knowledge\ProviderManager;
use App\Utils\ActivityUtils;
use App\Utils\CommentableUtils;
use App\Utils\LikableUtils;
use App\Utils\LocalisableUtils;
use App\Utils\PaginatorUtils;
use App\Utils\SearchUtils;
use App\Utils\WatchableUtils;
use App\Utils\ReviewableUtils;

/**
 * @Route("/fournisseurs")
 */
class ProviderController extends AbstractController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_provider_new")
	 * @Template("Knowledge/Provider/new.html.twig")
	 */
	public function new() {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_provider_new)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::class);

		$newProvider = new NewProvider();
		$form = $this->createForm(NewProviderType::class, $newProvider);

		return array(
			'form' => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_provider_create")
	 * @Template("Knowledge/Provider/new.html.twig")
	 */
	public function create(Request $request) {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_provider_new)');
		}

		$this->createLock('core_provider_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$dispatcher = $this->get('event_dispatcher');

		$newProvider = new NewProvider();
		$form = $this->createForm(NewProviderType::class, $newProvider);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$signValue = $newProvider->getSignValue();
			$logoValue = $newProvider->getLogoValue();
			$user = $this->getUser();

			$provider = new Provider();
			$provider->setSign($signValue->getData());
			$provider->setBrand($signValue->getBrand());
			$provider->setStore($signValue->getStore());
			$provider->incrementContributorCount();

			$om->persist($provider);
			$om->flush();	// Need to save provider to be sure ID is generated

			$provider->addSignValue($signValue);
			$provider->addLogoValue($logoValue);

			// Dispatch knowledge events
			$dispatcher->dispatch(new KnowledgeEvent($provider, array( 'field' => Provider::FIELD_SIGN, 'value' => $signValue )), KnowledgeListener::FIELD_VALUE_ADDED);
			$dispatcher->dispatch(new KnowledgeEvent($provider, array( 'field' => Provider::FIELD_LOGO, 'value' => $logoValue )), KnowledgeListener::FIELD_VALUE_ADDED);

			$signValue->setParentEntity($provider);
			$signValue->setParentEntityField(Provider::FIELD_SIGN);
			$signValue->setUser($user);

			$logoValue->setParentEntity($provider);
			$logoValue->setParentEntityField(Provider::FIELD_LOGO);
			$logoValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Sign and Logo of this new provider

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createContributeActivity($signValue, false);
			$activityUtils->createContributeActivity($logoValue, false);

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($provider), PublicationListener::PUBLICATION_CREATED);

			$om->flush();

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($provider), PublicationListener::PUBLICATION_PUBLISHED);

			return $this->redirect($this->generateUrl('core_provider_show', array('id' => $provider->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		return array(
			'newProvider' => $newProvider,
			'form'        => $form->createView(),
			'hideWarning' => true,
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_provider_delete")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_provider_delete)")
	 */
	public function delete($id) {

		$provider = $this->retrievePublication($id, Provider::CLASS_NAME);
		$this->assertDeletable($provider);

		// Delete
		$providerManager = $this->get(ProviderManager::class);
		$providerManager->delete($provider);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.provider.form.alert.delete_success', array( '%title%' => $provider->getTitle() )));

		return $this->redirect($this->generateUrl('core_provider_list'));
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_provider_location", defaults={"_format" = "json"})
	 * @Template("Knowledge/Provider/location.geojson.twig")
	 */
	public function location(Request $request, $id) {

		$provider = $this->retrievePublication($id, Provider::CLASS_NAME);
		$this->assertShowable($provider);

		$features = array();
		if (!is_null($provider->getLongitude()) && !is_null($provider->getLatitude())) {
			$properties = array(
				'color'   => 'blue',
				'cardUrl' => $this->generateUrl('core_provider_card', array('id' => $provider->getId())),
			);
			$gerometry = new \GeoJson\Geometry\Point($provider->getGeoPoint());
			$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
		}

		$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
		$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

		return array(
			'collection' => $collection,
		);
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_provider_widget")
	 * @Template("Knowledge/Provider/widget-xhr.html.twig")
	 */
	public function widget(Request $request, $id) {

		$provider = $this->retrievePublication($id, Provider::CLASS_NAME);
		$this->assertShowable($provider, true);

		return array(
			'provider' => $provider,
		);
	}

	/**
	 * @Route("/", name="core_provider_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_provider_list_page")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_provider_list_geojson")
	 * @Template("Knowledge/Provider/list.html.twig")
	 */
	public function list(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_provider_list_page)');
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

					case 'brand':

						$filter = new \Elastica\Query\Match('brand', $facet->value);
						$filters[] = $filter;

						break;

					case 'products':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'products' ));
						$filters[] = $filter;

						break;

					case 'services':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'services' ));
						$filters[] = $filter;

						break;

					case 'woods':

						$query1 = new \Elastica\Query\QueryString('"Bois massif"');
						$query1->setFields(array( 'products' ));
						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::class);
						$query2 = $elasticaQueryUtils->createShouldMatchPhraseQuery('woodsWorkaround', $facet->value);
						$filter = new \Elastica\Query\BoolQuery();
						$filter->addMust($query1);
						$filter->addMust($query2);
						$filters[] = $filter;

						break;

					case 'branches':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'branches' ));
						$filters[] = $filter;

						break;

					case 'in-store-selling':

						$filter = new \Elastica\Query\Range('inStoreSelling', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'mail-order-selling':

						$filter = new \Elastica\Query\Range('mailOrderSelling', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'sale-to-individuals':

						$filter = new \Elastica\Query\Range('saleToIndividuals', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'pro-only':

						$filter = new \Elastica\Query\Range('saleToIndividuals', array( 'lt' => true ));
						$filters[] = $filter;

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

					case 'around':

						if (isset($facet->value)) {
							$filter = new \Elastica\Query\GeoDistance('geoPoint', $facet->value, '100km');
							$filters[] = $filter;
						}

						break;

					case 'with-review':

						$filter = new \Elastica\Query\Range('reviewCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-creations':

						$filter = new \Elastica\Query\Range('creationCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-howtos':

						$filter = new \Elastica\Query\Range('howtoCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'rejected':

						$filter = new \Elastica\Query\Range('signRejected', array( 'gte' => 1 ));
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

					case 'sort-popular-rating':
						$sort = array( 'averageRating' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
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
							$filter->setFields(array( 'sign^100', 'geographicalAreas^50', 'products', 'services', 'description' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) {

				$filters[] = new \Elastica\Query\Range('signRejected', array( 'lt' => true ));

			},
			'knowledge_provider',
			\App\Entity\Knowledge\Provider::CLASS_NAME,
			'core_provider_list_page',
			$routeParameters
		);

		$parameters = array_merge($searchParameters, array(
			'providers'       => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters,
		));

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $provider) {
				$properties = array(
					'color'   => 'blue',
					'cardUrl' => $this->generateUrl('core_provider_card', array('id' => $provider->getId())),
				);
				$gerometry = new \GeoJson\Geometry\Point($provider->getGeoPoint());
				$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
			}
			$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
			$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

			$parameters = array_merge($parameters, array(
				'collection' => $collection,
			));

			return $this->render('Knowledge/Provider/list-xhr.geojson.twig', $parameters);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('Knowledge/Provider/list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('Knowledge/Provider/list-xhr.html.twig', $parameters);
			}
		}

		if ($layout == 'choice') {
			return $this->render('Knowledge/Provider/list-choice.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}/creations", requirements={"id" = "\d+"}, name="core_provider_creations")
	 * @Route("/{id}/creations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_provider_creations_filter")
	 * @Route("/{id}/creations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_provider_creations_filter_page")
	 * @Template("Knowledge/Provider/creations.html.twig")
	 */
	public function creations(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);

		$provider = $providerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($provider)) {
			throw $this->createNotFoundException('Unable to find Provider entity (id='.$id.').');
		}

		// Creations

		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByProvider($provider, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_provider_creations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

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
			'provider' => $provider,
		));
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_provider_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_provider_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_provider_howtos_filter_page")
	 * @Template("Knowledge/Provider/howtos.html.twig")
	 */
	public function howtos(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);

		$provider = $providerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($provider)) {
			throw $this->createNotFoundException('Unable to find Provider entity (id='.$id.').');
		}

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $howtoRepository->findPaginedByProvider($provider, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_howtos_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'howtos'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Creation/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'provider' => $provider,
		));
	}

	/**
	 * @Route("/{id}/card.xhr", name="core_provider_card")
	 * @Template("Knowledge/Provider/card-xhr.html.twig")
	 */
	public function card(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_provider_card)');
		}

		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);

		$id = intval($id);

		$provider = $providerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($provider)) {
			throw $this->createNotFoundException('Unable to find Provider entity (id='.$id.').');
		}

		return array(
			'provider' => $provider,
		);
	}

	/**
	 * @Route("/{id}.html", name="core_provider_show")
	 * @Template("Knowledge/Provider/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$provider = $providerRepository->findOneById($id);
		if (is_null($provider)) {
			if ($response = $witnessManager->checkResponse(Provider::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Provider entity (id='.$id.').');
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($provider), PublicationListener::PUBLICATION_SHOWN);

		$searchUtils = $this->get(SearchUtils::class);
		$elasticaQueryUtils = $this->get(ElasticaQueryUtils::class);
		$searchableStoreCount = $searchUtils->searchEntitiesCount(array( new \Elastica\Query\Match('brand', $provider->getBrand()) ), 'knowledge_provider');
		$searchableWoodCount = $searchUtils->searchEntitiesCount(array( $elasticaQueryUtils->createShouldMatchPhraseQuery('name', $provider->getWoods()) ), 'knowledge_wood');

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$reviewableUtils = $this->get(ReviewableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);

		return array(
			'provider'             => $provider,
			'permissionContext'    => $this->getPermissionContext($provider),
			'searchableStoreCount' => $searchableStoreCount,
			'searchableWoodCount'  => $searchableWoodCount,
			'likeContext'          => $likableUtils->getLikeContext($provider, $this->getUser()),
			'watchContext'         => $watchableUtils->getWatchContext($provider, $this->getUser()),
			'commentContext'       => $commentableUtils->getCommentContext($provider),
			'collectionContext'    => $collectionnableUtils->getCollectionContext($provider),
			'reviewContext'        => $reviewableUtils->getReviewContext($provider),
			'hasMap'               => !is_null($provider->getLatitude()) && !is_null($provider->getLongitude()),
		);
	}

}
