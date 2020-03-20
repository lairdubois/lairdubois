<?php

namespace Ladb\CoreBundle\Controller\Event;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\LocalisableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Form\Type\Event\EventType;
use Ladb\CoreBundle\Entity\Event\Event;
use Ladb\CoreBundle\Entity\Event\Content\Gallery;
use Ladb\CoreBundle\Model\LocalisableInterface;
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
use Ladb\CoreBundle\Manager\Event\EventManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\EventUtils;
use Ladb\CoreBundle\Utils\JoinableUtils;

/**
 * @Route("/evenements")
 */
class EventController extends AbstractController {

	/**
	 * @Route("/new", name="core_event_new")
	 * @Template("LadbCoreBundle:Event/Event:new.html.twig")
	 */
	public function newAction() {

		$event = new Event();
		$event->addBodyBlock(new \Ladb\CoreBundle\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(EventType::class, $event);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($event),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_event_create")
	 * @Template("LadbCoreBundle:Event/Event:new.html.twig")
	 */
	public function createAction(Request $request) {

		$this->createLock('core_event_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$event = new Event();
		$form = $this->createForm(EventType::class, $event);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($event);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($event);

			$event->setUser($this->getUser());
			$event->setMainPicture($mainPicture = $event->getPictures()->first() ? $mainPicture = $event->getPictures()->first() : null);
			$this->getUser()->getMeta()->incrementPrivateEventCount();

			$om->persist($event);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($event));

			return $this->redirect($this->generateUrl('core_event_show', array('id' => $event->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'event'        => $event,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($event),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_event_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_event_unlock")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_event_lock)")
	 */
	public function lockUnlockAction($id, $lock) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);

		$event = $eventRepository->findOneById($id);
		if (is_null($event)) {
			throw $this->createNotFoundException('Unable to event Event entity (id='.$id.').');
		}
		if ($event->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_event_lock or core_event_unlock)');
		}

		// Lock or Unlock
		$eventManager = $this->get(EventManager::NAME);
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
	public function publishAction($id) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);

		$event = $eventRepository->findOneByIdJoinedOnUser($id);
		if (is_null($event)) {
			throw $this->createNotFoundException('Unable to event Event entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $event->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_event_publish)');
		}
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not emailConfirmed (core_event_publish)');
		}
		if ($event->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_event_publish)');
		}
		if ($event->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked (core_event_publish)');
		}

		// Publish
		$eventManager = $this->get(EventManager::NAME);
		$eventManager->publish($event);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('event.event.form.alert.publish_success', array( '%title%' => $event->getTitle() )));

		return $this->redirect($this->generateUrl('core_event_show', array( 'id' => $event->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_event_unpublish")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_event_unpublish)")
	 */
	public function unpublishAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);

		$event = $eventRepository->findOneByIdJoinedOnUser($id);
		if (is_null($event)) {
			throw $this->createNotFoundException('Unable to event Event entity (id='.$id.').');
		}
		if ($event->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_event_unpublish)');
		}

		// Unpublish
		$eventManager = $this->get(EventManager::NAME);
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
	 * @Template("LadbCoreBundle:Event/Event:edit.html.twig")
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);

		$event = $eventRepository->findOneById($id);
		if (is_null($event)) {
			throw $this->createNotFoundException('Unable to event Event entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $event->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_event_edit)');
		}

		$form = $this->createForm(EventType::class, $event);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'event'         => $event,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($event),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_event_update")
	 * @Template("LadbCoreBundle:Event/Event:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);

		$event = $eventRepository->findOneById($id);
		if (is_null($event)) {
			throw $this->createNotFoundException('Unable to event Event entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $event->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_event_update)');
		}

		$originalBodyBlocks = $event->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $event->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(EventType::class, $event);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($event, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($event);

			$event->setMainPicture($mainPicture = $event->getPictures()->first() ? $mainPicture = $event->getPictures()->first() : null);
			if ($event->getUser()->getId() == $this->getUser()->getId()) {
				$event->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($event, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('event.event.form.alert.update_success', array( '%title%' => $event->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(EventType::class, $event);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'event'         => $event,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($event),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_event_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);

		$event = $eventRepository->findOneById($id);
		if (is_null($event)) {
			throw $this->createNotFoundException('Unable to event Event entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !($event->getIsDraft() === true && $event->getUser()->getId() == $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_event_delete)');
		}

		// Delete
		$eventManager = $this->get(EventManager::NAME);
		$eventManager->delete($event);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('event.event.form.alert.delete_success', array( '%title%' => $event->getTitle() )));

		return $this->redirect($this->generateUrl('core_event_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_event_widget")
	 * @Template("LadbCoreBundle:Event/Event:widget-xhr.html.twig")
	 */
	public function widgetAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);

		$id = intval($id);

		$event = $eventRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($event)) {
			throw $this->createNotFoundException('Unable to event Event entity (id='.$id.').');
		}
		if ($event->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_event_widget)');
		}

		return array(
			'event' => $event,
		);
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_event_location", defaults={"_format" = "json"})
	 * @Template("LadbCoreBundle:Event/Event:location.geojson.twig")
	 */
	public function locationAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);

		$id = intval($id);

		$event = $eventRepository->findOneById($id);
		if (is_null($event)) {
			throw $this->createNotFoundException('Unable to event Workshop entity (id='.$id.').');
		}
		if ($event->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $event->getUser()->getId() != $this->getUser()->getId())) {
				throw $this->createNotFoundException('Not allowed (core_event_location)');
			}
		}

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
	 * @Route("/", name="core_event_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_event_list_page")
	 * @Route(".json", defaults={"_format" = "json", "page"=-1, "layout"="json"}, name="core_event_list_json")
	 * @Template("LadbCoreBundle:Event/Event:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::NAME);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_event_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
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

					case 'period':

						if ($facet->value == 'last7days') {

							$filters[] = new \Elastica\Query\Range('changedAt', array('gte' => 'now-7d/d'));

						} elseif ($facet->value == 'last30days') {

							$filters[] = new \Elastica\Query\Range('changedAt', array('gte' => 'now-30d/d'));

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

					case 'sort-popular-joins':
						$sort = array( 'joinCount' => array( 'order' => 'desc' ) );
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

				$user = $this->getUser();
				$publicVisibilityFilter = new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC ));
				if (!is_null($user)) {

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
			'fos_elastica.index.ladb.event_event',
			\Ladb\CoreBundle\Entity\Event\Event::CLASS_NAME,
			'core_event_list_page'
		);

		$parameters = array_merge($searchParameters, array(
			'events' => $searchParameters['entities'],
		));

		if ($layout == 'json') {
			return $this->render('LadbCoreBundle:Event/Event:list-xhr.json.twig', $parameters);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Event/Event:list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateEventCount() > 0) {

			$draftPath = $this->generateUrl('core_event_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateEventCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->transchoice('event.event.choice.draft_alert', $draftCount, array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_event_show")
	 * @Template("LadbCoreBundle:Event/Event:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$eventRepository = $om->getRepository(Event::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$event = $eventRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($event)) {
			if ($response = $witnessManager->checkResponse(Event::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to event Event entity (id='.$id.').');
		}
		if ($event->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $event->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Event::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_event_show)');
			}
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($event));

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userEvents = $explorableUtils->getPreviousAndNextPublishedUserExplorables($event, $eventRepository, $event->getUser()->getMeta()->getPublicEventCount());
		$similarEvents = $explorableUtils->getSimilarExplorables($event, 'fos_elastica.index.ladb.event_event', Event::CLASS_NAME, $userEvents);

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);
		$joinableUtils = $this->get(JoinableUtils::NAME);

		if ($event instanceof LocalisableInterface) {
			$hasMap = !is_null($event->getLatitude()) && !is_null($event->getLongitude());
		} else {
			$hasMap = false;
		}

		return array(
			'event'             => $event,
			'userEvents'        => $userEvents,
			'similarEvents'     => $similarEvents,
			'likeContext'       => $likableUtils->getLikeContext($event, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($event, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($event),
			'collectionContext' => $collectionnableUtils->getCollectionContext($event),
			'followerContext'   => $followerUtils->getFollowerContext($event->getUser(), $this->getUser()),
			'joinContext'       => $joinableUtils->getJoinContext($event, $this->getUser()),
			'hasMap'            => $hasMap,
		);
	}

}