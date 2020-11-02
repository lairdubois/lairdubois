<?php

namespace Ladb\CoreBundle\Controller\Knowledge;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\ElasticaQueryUtils;
use Ladb\CoreBundle\Utils\KnowledgeUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Knowledge\Provider;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Event\KnowledgeEvent;
use Ladb\CoreBundle\Event\KnowledgeListener;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Form\Model\NewProvider;
use Ladb\CoreBundle\Form\Type\Knowledge\NewProviderType;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Manager\Knowledge\ProviderManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\LocalisableUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\PropertyUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\ReviewableUtils;


/**
 * @Route("/fournisseurs")
 */
class ProviderController extends AbstractController {

	/**
	 * @Route("/new", name="core_provider_new")
	 * @Template("LadbCoreBundle:Knowledge/Provider:new.html.twig")
	 */
	public function newAction() {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_provider_new)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::NAME);

		$newProvider = new NewProvider();
		$form = $this->createForm(NewProviderType::class, $newProvider);

		return array(
			'form' => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_provider_create")
	 * @Template("LadbCoreBundle:Knowledge/Provider:new.html.twig")
	 */
	public function createAction(Request $request) {

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
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($provider, array( 'field' => Provider::FIELD_SIGN, 'value' => $signValue )));
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($provider, array( 'field' => Provider::FIELD_LOGO, 'value' => $logoValue )));

			$signValue->setParentEntity($provider);
			$signValue->setParentEntityField(Provider::FIELD_SIGN);
			$signValue->setUser($user);

			$logoValue->setParentEntity($provider);
			$logoValue->setParentEntityField(Provider::FIELD_LOGO);
			$logoValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Sign and Logo of this new provider

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createContributeActivity($signValue, false);
			$activityUtils->createContributeActivity($logoValue, false);

			// Dispatch publication event
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($provider));

			$om->flush();

			// Dispatch publication event
			$dispatcher->dispatch(PublicationListener::PUBLICATION_PUBLISHED, new PublicationEvent($provider));

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
	public function deleteAction($id) {
		$propertyUtils = $this->get(PropertyUtils::NAME);
		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);

		$provider = $providerRepository->findOneById($id);
		if (is_null($provider)) {
			throw $this->createNotFoundException('Unable to find Provider entity (id='.$id.').');
		}

		// Delete
		$providerManager = $this->get(ProviderManager::NAME);
		$providerManager->delete($provider);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.provider.form.alert.delete_success', array( '%title%' => $provider->getTitle() )));

		return $this->redirect($this->generateUrl('core_provider_list'));
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_provider_location", defaults={"_format" = "json"})
	 * @Template("LadbCoreBundle:Knowledge/Provider:location.geojson.twig")
	 */
	public function locationAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);

		$id = intval($id);

		$provider = $providerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($provider)) {
			throw $this->createNotFoundException('Unable to find Provider entity (id='.$id.').');
		}

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
	 * @Template("LadbCoreBundle:Knowledge/Provider:widget-xhr.html.twig")
	 */
	public function widgetAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);

		$id = intval($id);

		$provider = $providerRepository->findOneById($id);
		if (is_null($provider)) {
			throw $this->createNotFoundException('Unable to find Provider entity (id='.$id.').');
		}

		return array(
			'provider' => $provider,
		);
	}

	/**
	 * @Route("/", name="core_provider_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_provider_list_page")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_provider_list_geojson")
	 * @Template("LadbCoreBundle:Knowledge/Provider:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::NAME);

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
						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
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

						$filter = new \Elastica\Query\Range('saleToIndividuals', array( 'lt' => 1 ));
						$filters[] = $filter;

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

				$filters[] = new \Elastica\Query\Range('signRejected', array( 'lt' => 1 ));

			},
			'fos_elastica.index.ladb.knowledge_provider',
			\Ladb\CoreBundle\Entity\Knowledge\Provider::CLASS_NAME,
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

			return $this->render('LadbCoreBundle:Knowledge/Provider:list-xhr.geojson.twig', $parameters);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('LadbCoreBundle:Knowledge/Provider:list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('LadbCoreBundle:Knowledge/Provider:list-xhr.html.twig', $parameters);
			}
		}

		if ($layout == 'choice') {
			return $this->render('LadbCoreBundle:Knowledge/Provider:list-choice.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}/creations", requirements={"id" = "\d+"}, name="core_provider_creations")
	 * @Route("/{id}/creations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_provider_creations_filter")
	 * @Route("/{id}/creations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_provider_creations_filter_page")
	 * @Template("LadbCoreBundle:Knowledge/Provider:creations.html.twig")
	 */
	public function creationsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);

		$provider = $providerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($provider)) {
			throw $this->createNotFoundException('Unable to find Provider entity (id='.$id.').');
		}

		// Creations

		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'provider' => $provider,
		));
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_provider_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_provider_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_provider_howtos_filter_page")
	 * @Template("LadbCoreBundle:Knowledge/Provider:howtos.html.twig")
	 */
	public function howtosAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);

		$provider = $providerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($provider)) {
			throw $this->createNotFoundException('Unable to find Provider entity (id='.$id.').');
		}

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'provider' => $provider,
		));
	}

	/**
	 * @Route("/{id}/card.xhr", name="core_provider_card")
	 * @Template("LadbCoreBundle:Knowledge/Provider:card-xhr.html.twig")
	 */
	public function cardAction(Request $request, $id) {
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
	 * @Template("LadbCoreBundle:Knowledge/Provider:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$providerRepository = $om->getRepository(Provider::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

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
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($provider));

		$searchUtils = $this->get(SearchUtils::NAME);
		$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
		$searchableStoreCount = $searchUtils->searchEntitiesCount(array( new \Elastica\Query\Match('brand', $provider->getBrand()) ), 'fos_elastica.index.ladb.knowledge_provider');
		$searchableWoodCount = $searchUtils->searchEntitiesCount(array( $elasticaQueryUtils->createShouldMatchPhraseQuery('name', $provider->getWoods()) ), 'fos_elastica.index.ladb.knowledge_wood');

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);

		return array(
			'provider'             => $provider,
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
