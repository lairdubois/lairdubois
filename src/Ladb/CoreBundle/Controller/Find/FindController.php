<?php

namespace Ladb\CoreBundle\Controller\Find;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Controller\PublicationControllerTrait;
use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Utils\LocalisableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Form\Type\Find\FindType;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Find\Content\Gallery;
use Ladb\CoreBundle\Model\LocalisableInterface;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Manager\Find\FindManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\FindUtils;

/**
 * @Route("/trouvailles")
 */
class FindController extends AbstractController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_find_new")
	 * @Template("LadbCoreBundle:Find/Find:new.html.twig")
	 */
	public function newAction(Request $request) {

		$find = new Find();
		$find->addBodyBlock(new \Ladb\CoreBundle\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(FindType::class, $find);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'owner'        => $this->retrieveOwner($request),
			'tagProposals' => $tagUtils->getProposals($find),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_find_create")
	 * @Template("LadbCoreBundle:Find/Find:new.html.twig")
	 */
	public function createAction(Request $request) {

		$owner = $this->retrieveOwner($request);

		$this->createLock('core_find_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$find = new Find();
		$form = $this->createForm(FindType::class, $find);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($find);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($find);

			$findUtils = $this->get(FindUtils::NAME);
			$findUtils->generateMainPicture($find);

			$find->setUser($owner);
			$owner->getMeta()->incrementPrivateFindCount();

			$om->persist($find);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($find));

			return $this->redirect($this->generateUrl('core_find_show', array('id' => $find->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'find'         => $find,
			'form'         => $form->createView(),
			'owner'        => $owner,
			'tagProposals' => $tagUtils->getProposals($find),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_find_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_find_unlock")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_find_lock)")
	 */
	public function lockUnlockAction($id, $lock) {

		$find = $this->retrievePublication($id, Find::CLASS_NAME);
		$this->assertLockUnlockable($find, $lock);

		// Lock or Unlock
		$findManager = $this->get(FindManager::NAME);
		if ($lock) {
			$findManager->lock($find);
		} else {
			$findManager->unlock($find);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('find.find.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $find->getTitle() )));

		return $this->redirect($this->generateUrl('core_find_show', array( 'id' => $find->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_find_publish")
	 */
	public function publishAction($id) {

		$find = $this->retrievePublication($id, Find::CLASS_NAME);
		$this->assertPublishable($find);

		// Publish
		$findManager = $this->get(FindManager::NAME);
		$findManager->publish($find);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('find.find.form.alert.publish_success', array( '%title%' => $find->getTitle() )));

		return $this->redirect($this->generateUrl('core_find_show', array( 'id' => $find->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_find_unpublish")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_find_unpublish)")
	 */
	public function unpublishAction(Request $request, $id) {

		$find = $this->retrievePublication($id, Find::CLASS_NAME);
		$this->assertUnpublishable($find);

		// Unpublish
		$findManager = $this->get(FindManager::NAME);
		$findManager->unpublish($find);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('find.find.form.alert.unpublish_success', array( '%title%' => $find->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_find_edit")
	 * @Template("LadbCoreBundle:Find/Find:edit.html.twig")
	 */
	public function editAction($id) {

		$find = $this->retrievePublication($id, Find::CLASS_NAME);
		$this->assertEditabable($find);

		$form = $this->createForm(FindType::class, $find);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'find'         => $find,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($find),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_find_update")
	 * @Template("LadbCoreBundle:Find/Find:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		$doUp = $request->get('ladb_do_up', false) && $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');

		$find = $this->retrievePublication($id, Find::CLASS_NAME);
		$this->assertEditabable($find);

		if ($find->getContent() instanceof Gallery) {
			$picturedUtils = $this->get(PicturedUtils::NAME);
			$picturedUtils->resetPictures($find->getContent()); // Reset pictures array to consider form pictures order
		}

		$originalBodyBlocks = $find->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $find->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(FindType::class, $find);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($find, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($find);

			$findUtils = $this->get(FindUtils::NAME);
			$findUtils->generateMainPicture($find);

			if ($doUp) {
				$find->setChangedAt(new \DateTime());
			}
			if ($find->getUser() == $this->getUser()) {
				$find->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			if ($doUp) {
				$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($find));
			}
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($find, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('find.find.form.alert.update_success', array( '%title%' => $find->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(FindType::class, $find);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'find'         => $find,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($find),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_find_delete")
	 */
	public function deleteAction($id) {

		$find = $this->retrievePublication($id, Find::CLASS_NAME);
		$this->assertDeletable($find);

		// Delete
		$findManager = $this->get(FindManager::NAME);
		$findManager->delete($find);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('find.find.form.alert.delete_success', array( '%title%' => $find->getTitle() )));

		return $this->redirect($this->generateUrl('core_find_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_find_widget")
	 * @Template("LadbCoreBundle:Find/Find:widget-xhr.html.twig")
	 */
	public function widgetAction($id) {

		$find = $this->retrievePublication($id, Find::CLASS_NAME);
		$this->assertShowable($find, true);

		return array(
			'find' => $find,
		);
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_find_location", defaults={"_format" = "json"})
	 * @Template("LadbCoreBundle:Find/Find:location.geojson.twig")
	 */
	public function locationAction($id) {

		$find = $this->retrievePublication($id, Find::CLASS_NAME);
		$this->assertShowable($find);

		$features = array();
		$content = $find->getContent();
		if (!is_null($content->getLongitude()) && !is_null($content->getLatitude())) {
			$properties = array(
				'color' => 'orange',
			);
			$gerometry = new \GeoJson\Geometry\Point($content->getGeoPoint());
			$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
		}

		$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
		$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

		return array(
			'collection' => $collection,
		);
	}

	/**
	 * @Route("/{id}/card.xhr", name="core_find_card")
	 * @Template("LadbCoreBundle:Find/Find:card-xhr.html.twig")
	 */
	public function cardAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_find_card-');
		}

		$find = $this->retrievePublication($id, Find::CLASS_NAME);
		$this->assertShowable($find);

		return array(
			'find' => $find,
		);
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_find_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_find_list_filter_page")
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_find_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_find_list_page")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_find_list_geojson")
	 * @Template("LadbCoreBundle:Find/Find:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::NAME);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_find_list_page)');
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

							$filters[] = new \Elastica\Query\Range('createdAt', array( 'gte' => 'now-7d/d' ));

						} elseif ($facet->value == 'last30days') {

							$filters[] = new \Elastica\Query\Range('createdAt', array( 'gte' => 'now-30d/d' ));

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

					case 'kind':

						$filter = new \Elastica\Query\MatchPhrase('kind', $facet->value);
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
			'fos_elastica.index.ladb.find_find',
			\Ladb\CoreBundle\Entity\Find\Find::CLASS_NAME,
			'core_find_list_page'
		);

		$parameters = array_merge($searchParameters, array(
			'finds' => $searchParameters['entities'],
		));

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $find) {
				$geoPoint = $find->getGeoPoint();
				if (is_null($geoPoint)) {
					continue;
				}
				$properties = array(
					'color'   => 'orange',
					'cardUrl' => $this->generateUrl('core_find_card', array('id' => $find->getId())),
				);
				$gerometry = new \GeoJson\Geometry\Point($geoPoint);
				$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
			}
			$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
			$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

			$parameters = array_merge($parameters, array(
				'collection' => $collection,
			));

			return $this->render('LadbCoreBundle:Find/Find:list-xhr.geojson.twig', $parameters);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Find/Find:list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateFindCount() > 0) {

			$draftPath = $this->generateUrl('core_find_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateFindCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->transchoice('find.find.choice.draft_alert', $draftCount, array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_find_show")
	 * @Template("LadbCoreBundle:Find/Find:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$findRepository = $om->getRepository(Find::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$find = $findRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($find)) {
			if ($response = $witnessManager->checkResponse(Find::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Find entity (id='.$id.').');
		}
		if ($find->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $find->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Find::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_find_show)');
			}
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($find));

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userFinds = $explorableUtils->getPreviousAndNextPublishedUserExplorables($find, $findRepository, $find->getUser()->getMeta()->getPublicFindCount());
		$similarFinds = $explorableUtils->getSimilarExplorables($find, 'fos_elastica.index.ladb.find_find', Find::CLASS_NAME, $userFinds);

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		if ($find->getContent() instanceof LocalisableInterface) {
			$hasMap = !is_null($find->getContent()->getLatitude()) && !is_null($find->getContent()->getLongitude());
		} else {
			$hasMap = false;
		}

		return array(
			'find'              => $find,
			'permissionContext' => $this->getPermissionContext($find),
			'userFinds'         => $userFinds,
			'similarFinds'      => $similarFinds,
			'likeContext'       => $likableUtils->getLikeContext($find, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($find, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($find),
			'collectionContext' => $collectionnableUtils->getCollectionContext($find),
			'followerContext'   => $followerUtils->getFollowerContext($find->getUser(), $this->getUser()),
			'hasMap'            => $hasMap,
		);
	}

}