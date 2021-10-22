<?php

namespace App\Controller\Event;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Core\Member;
use App\Utils\CollectionnableUtils;
use App\Utils\FeedbackableUtils;
use App\Utils\LocalisableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Form\Type\Event\EventType;
use App\Entity\Event\Event;
use App\Entity\Event\Content\Gallery;
use App\Model\LocalisableInterface;
use App\Utils\LikableUtils;
use App\Utils\WatchableUtils;
use App\Utils\CommentableUtils;
use App\Utils\FollowerUtils;
use App\Utils\TagUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\PicturedUtils;
use App\Utils\SearchUtils;
use App\Utils\ExplorableUtils;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Manager\Event\EventManager;
use App\Manager\Core\WitnessManager;
use App\Model\HiddableInterface;
use App\Utils\BlockBodiedUtils;
use App\Utils\EventUtils;
use App\Utils\JoinableUtils;

/**
 * @Route("/evenements")
 */
class EventController extends AbstractController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_event_new")
	 * @Template("Event/Event/new.html.twig")
	 */
	public function new(Request $request) {

		$event = new Event();
		$event->addBodyBlock(new \App\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(EventType::class, $event);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'owner'        => $this->retrieveOwner($request),
			'tagProposals' => $tagUtils->getProposals($event),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_event_create")
	 * @Template("Event/Event/new.html.twig")
	 */
	public function create(Request $request) {

		$owner = $this->retrieveOwner($request);

		$this->createLock('core_event_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$event = new Event();
		$form = $this->createForm(EventType::class, $event);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($event);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($event);

			$event->setUser($owner);
			$event->setMainPicture($mainPicture = $event->getPictures()->first() ? $mainPicture = $event->getPictures()->first() : null);
			$owner->getMeta()->incrementPrivateEventCount();

			$om->persist($event);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($event), PublicationListener::PUBLICATION_CREATED);

			return $this->redirect($this->generateUrl('core_event_show', array('id' => $event->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'event'        => $event,
			'form'         => $form->createView(),
			'owner'        => $owner,
			'tagProposals' => $tagUtils->getProposals($event),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_event_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_event_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertLockUnlockable($event, $lock);

		// Lock or Unlock
		$eventManager = $this->get(EventManager::class);
		if ($lock) {
			$eventManager->lock($event);
		} else {
			$eventManager->unlock($event);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('event.event.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $event->getTitle() )));

		return $this->redirect($this->generateUrl('core_event_show', array( 'id' => $event->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_event_publish")
	 */
	public function publish($id) {

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertPublishable($event);

		// Publish
		$eventManager = $this->get(EventManager::class);
		$eventManager->publish($event);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('event.event.form.alert.publish_success', array( '%title%' => $event->getTitle() )));

		return $this->redirect($this->generateUrl('core_event_show', array( 'id' => $event->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_event_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertUnpublishable($event);

		// Unpublish
		$eventManager = $this->get(EventManager::class);
		$eventManager->unpublish($event);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('event.event.form.alert.unpublish_success', array( '%title%' => $event->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_event_edit")
	 * @Template("Event/Event/edit.html.twig")
	 */
	public function edit($id) {

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertEditabable($event);

		$form = $this->createForm(EventType::class, $event);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'event'         => $event,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($event),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_event_update")
	 * @Template("Event/Event/edit.html.twig")
	 */
	public function update(Request $request, $id) {

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertEditabable($event);

		$originalBodyBlocks = $event->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $event->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(EventType::class, $event);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($event, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($event);

			$event->setMainPicture($mainPicture = $event->getPictures()->first() ? $mainPicture = $event->getPictures()->first() : null);
			if ($event->getUser() == $this->getUser()) {
				$event->setUpdatedAt(new \DateTime());
			}

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($event, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('event.event.form.alert.update_success', array( '%title%' => $event->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(EventType::class, $event);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'event'         => $event,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($event),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_event_delete")
	 */
	public function delete($id) {

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertDeletable($event);

		// Delete
		$eventManager = $this->get(EventManager::class);
		$eventManager->delete($event);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('event.event.form.alert.delete_success', array( '%title%' => $event->getTitle() )));

		return $this->redirect($this->generateUrl('core_event_list'));
	}

	/**
	 * @Route("/{id}/chown", requirements={"id" = "\d+"}, name="core_event_chown")
	 */
	public function chown(Request $request, $id) {

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertChownable($event);

		$targetUser = $this->retrieveOwner($request);

		// Change owner
		$eventManager = $this->get(EventManager::class);
		$eventManager->changeOwner($event, $targetUser);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('event.event.form.alert.chown_success', array( '%title%' => $event->getTitle() )));

		return $this->redirect($this->generateUrl('core_event_show', array( 'id' => $event->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_event_widget")
	 * @Template("Event/Event/widget-xhr.html.twig")
	 */
	public function widget($id) {

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertShowable($event, true);

		return array(
			'event' => $event,
		);
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_event_location", defaults={"_format" = "json"})
	 * @Template("Event/Event/location.geojson.twig")
	 */
	public function location($id) {

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertShowable($event);

		$features = array();
		if (!is_null($event->getLongitude()) && !is_null($event->getLatitude())) {
			$properties = array(
				'color' => 'orange',
			);
			$gerometry = new \GeoJson\Geometry\Point($event->getGeoPoint());
			$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
		}

		$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
		$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

		return array(
			'collection' => $collection,
		);
	}

	/**
	 * @Route("/{id}/card.xhr", name="core_event_card")
	 * @Template("Event/Event/card-xhr.html.twig")
	 */
	public function card(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_event_card)');
		}

		$event = $this->retrievePublication($id, Event::CLASS_NAME);
		$this->assertShowable($event);

		return array(
			'event' => $event,
		);
	}

	/**
	 * @Route("/", name="core_event_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_event_list_page")
	 * @Route(".json", defaults={"_format" = "json", "page"=-1, "layout"="json"}, name="core_event_list_json")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_event_list_geojson")
	 * @Template("Event/Event/list.html.twig")
	 */
	public function list(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_event_list_page)');
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
									->addFilter(new \Elastica\Query\Range('visibility', array('lt' => HiddableInterface::VISIBILITY_PUBLIC)));

							} else {

								$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							}

							$filters[] = $filter;

						}

						break;

					case 'active':

						$filters[] = new \Elastica\Query\Range('cancelled', array('lt' => 1));

						break;

					case 'status':

						if ($facet->value == 'cancelled') {
							$filters[] = new \Elastica\Query\Range('cancelled', array('gte' => 1));
						} else {
							$filters[] = new \Elastica\Query\Range('cancelled', array('lt' => 1));
							switch ($facet->value) {
								case 'scheduled':
									$filters[] = new \Elastica\Query\Range('startAt', array('gte' => 'now'));
									break;
								case 'running':
									$filters[] = new \Elastica\Query\Range('startAt', array('lte' => 'now'));
									break;
							}
							$filters[] = new \Elastica\Query\Range('endAt', array('gte' => 'now'));
						}

						break;

					case 'day':

						$dateValue = (new \DateTime($facet->value));
						$formatedDateValue = $dateValue->format('Y-m-d');

						$filters[] = new \Elastica\Query\Range('startAt', array( 'lte' => $formatedDateValue ));
						$filters[] = new \Elastica\Query\Range('endAt', array( 'gte' => $formatedDateValue ));

						break;

					case 'month':

						$startDateValue = (new \DateTime($facet->value.'-01'));
						$formatedStartDateValue = $startDateValue->format('Y-m-d');
						$endDateValue = $startDateValue->add(new \DateInterval('P1M'))->sub(new \DateInterval('P1D'));
						$formatedEndDateValue = $endDateValue->format('Y-m-d');

						$filters[] = new \Elastica\Query\Range('startAt', array( 'lte' => $formatedEndDateValue ));
						$filters[] = new \Elastica\Query\Range('endAt', array( 'gte' => $formatedStartDateValue ));

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

					case 'with-feedback':

						$filter = new \Elastica\Query\Range('feedbackCount', array( 'gt' => 0 ));
						$filters[] = $filter;

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

					case 'sort-popular-joins':
						$sort = array( 'joinCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-date':
						$sort = array( 'startAt' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
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

				if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getUnlistedEventEventCount() > 0) {
					$sort = array('changedAt' => array('order' => 'desc'));
				} else {

					// By default (without new publication) it displays only rugging and scheduled events in startAt ASC order
					$sort = array( 'startAt' => array( 'order' => 'asc' ) );
					$filters[] = new \Elastica\Query\Range('endAt', array('gte' => 'now'));

				}

			},
			function(&$filters) {

				$this->pushGlobalVisibilityFilter($filters, true, true);

			},
			'event_event',
			\App\Entity\Event\Event::CLASS_NAME,
			'core_event_list_page'
		);

		$parameters = array_merge($searchParameters, array(
			'events' => $searchParameters['entities'],
		));

		if ($layout == 'json') {
			return $this->render('Event/Event/list-xhr.json.twig', $parameters);
		}

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $event) {
				$geoPoint = $event->getGeoPoint();
				if (is_null($geoPoint)) {
					continue;
				}
				if ($event->getCancelled()) {
					$color = 'red';
				} else if ($event->getStatus() == Event::STATUS_SCHEDULED) {
					$color = 'blue';
				} else if ($event->getStatus() == Event::STATUS_RUNNING) {
					$color = 'green';
				} else {
					$color = 'grey';
				}
				$properties = array(
					'color'   => $color,
					'cardUrl' => $this->generateUrl('core_event_card', array('id' => $event->getId())),
				);
				$gerometry = new \GeoJson\Geometry\Point($geoPoint);
				$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
			}
			$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
			$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

			$parameters = array_merge($parameters, array(
				'collection' => $collection,
			));

			return $this->render('Find/Find/list-xhr.geojson.twig', $parameters);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Event/Event/list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateEventCount() > 0) {

			$draftPath = $this->generateUrl('core_event_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateEventCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->trans('event.event.choice.draft_alert', array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_event_show")
	 * @Template("Event/Event/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$event = $eventRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($event)) {
			if ($response = $witnessManager->checkResponse(Event::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to event Event entity (id='.$id.').');
		}
		$this->assertShowable($event);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($event), PublicationListener::PUBLICATION_SHOWN);

		$explorableUtils = $this->get(ExplorableUtils::class);
		$userEvents = $explorableUtils->getPreviousAndNextPublishedUserExplorables($event, $eventRepository, $event->getUser()->getMeta()->getPublicEventCount());
		$similarEvents = $explorableUtils->getSimilarExplorables($event, 'event_event', Event::CLASS_NAME, $userEvents);

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$feedbackableUtils = $this->get(FeedbackableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
		$followerUtils = $this->get(FollowerUtils::class);
		$joinableUtils = $this->get(JoinableUtils::class);

		if ($event instanceof LocalisableInterface) {
			$hasMap = !is_null($event->getLatitude()) && !is_null($event->getLongitude());
		} else {
			$hasMap = false;
		}

		return array(
			'event'             => $event,
			'permissionContext' => $this->getPermissionContext($event),
			'userEvents'        => $userEvents,
			'similarEvents'     => $similarEvents,
			'likeContext'       => $likableUtils->getLikeContext($event, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($event, $this->getUser()),
			'feedbackContext'   => $feedbackableUtils->getFeedbackContext($event),
			'commentContext'    => $commentableUtils->getCommentContext($event),
			'collectionContext' => $collectionnableUtils->getCollectionContext($event),
			'followerContext'   => $followerUtils->getFollowerContext($event->getUser(), $this->getUser()),
			'joinContext'       => $joinableUtils->getJoinContext($event, $this->getUser()),
			'hasMap'            => $hasMap,
		);
	}

}