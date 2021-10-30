<?php

namespace App\Controller\Offer;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Offer\Offer;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Form\Type\Offer\OfferType;
use App\Manager\Core\WitnessManager;
use App\Manager\Offer\OfferManager;
use App\Model\HiddableInterface;
use App\Utils\BlockBodiedUtils;
use App\Utils\CollectionnableUtils;
use App\Utils\CommentableUtils;
use App\Utils\ExplorableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\FollowerUtils;
use App\Utils\LikableUtils;
use App\Utils\LocalisableUtils;
use App\Utils\PicturedUtils;
use App\Utils\SearchUtils;
use App\Utils\TagUtils;
use App\Utils\WatchableUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/annonces")
 */
class OfferController extends AbstractController {

	use PublicationControllerTrait;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.WitnessManager::class,
            '?'.OfferManager::class,
            '?'.BlockBodiedUtils::class,
            '?'.CollectionnableUtils::class,
            '?'.CommentableUtils::class,
            '?'.ExplorableUtils::class,
            '?'.FieldPreprocessorUtils::class,
            '?'.FollowerUtils::class,
            '?'.LikableUtils::class,
            '?'.LocalisableUtils::class,
            '?'.PicturedUtils::class,
            '?'.SearchUtils::class,
            '?'.TagUtils::class,
            '?'.WatchableUtils::class,
        ));
    }

	/**
	 * @Route("/new", name="core_offer_new")
	 * @Template("Offer/Offer/new.html.twig")
	 */
	public function new(Request $request) {

		$offer = new Offer();
		$offer->addBodyBlock(new \App\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(OfferType::class, $offer);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'owner'        => $this->retrieveOwner($request),
			'tagProposals' => $tagUtils->getProposals($offer),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_offer_create")
	 * @Template("Offer/Offer/new.html.twig")
	 */
	public function create(Request $request) {

		$owner = $this->retrieveOwner($request);

		$this->createLock('core_offer_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$offer = new Offer();
		$form = $this->createForm(OfferType::class, $offer);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($offer);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($offer);

			$offer->setUser($owner);
			$offer->setMainPicture($mainPicture = $offer->getPictures()->first() ? $mainPicture = $offer->getPictures()->first() : null);
			$owner->getMeta()->incrementPrivateOfferCount();

			$om->persist($offer);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($offer), PublicationListener::PUBLICATION_CREATED);

			return $this->redirect($this->generateUrl('core_offer_show', array('id' => $offer->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'offer'        => $offer,
			'form'         => $form->createView(),
			'owner'        => $owner,
			'tagProposals' => $tagUtils->getProposals($offer),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_offer_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_offer_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertLockUnlockable($offer, $lock);

		// Lock or Unlock
		$offerManager = $this->get(OfferManager::class);
		if ($lock) {
			$offerManager->lock($offer);
		} else {
			$offerManager->unlock($offer);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $offer->getTitle() )));

		return $this->redirect($this->generateUrl('core_offer_show', array( 'id' => $offer->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_offer_publish")
	 */
	public function publish($id) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertPublishable($offer, Offer::MAX_PUBLISH_COUNT);

		// Publish
		$offerManager = $this->get(OfferManager::class);
		$offerManager->publish($offer);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.form.alert.publish_success', array( '%title%' => $offer->getTitle() )));

		return $this->redirect($this->generateUrl('core_offer_show', array( 'id' => $offer->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_offer_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertUnpublishable($offer);

		// Unpublish
		$offerManager = $this->get(OfferManager::class);
		$offerManager->unpublish($offer);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.form.alert.unpublish_success', array( '%title%' => $offer->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_offer_edit")
	 * @Template("Offer/Offer/edit.html.twig")
	 */
	public function edit($id) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertEditabable($offer);

		$form = $this->createForm(OfferType::class, $offer);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'offer'        => $offer,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($offer),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_offer_update")
	 * @Template("Offer/Offer/edit.html.twig")
	 */
	public function update(Request $request, $id) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertEditabable($offer);

		$picturedUtils = $this->get(PicturedUtils::class);
		$picturedUtils->resetPictures($offer); // Reset pictures array to consider form pictures order

		$originalBodyBlocks = $offer->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $offer->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(OfferType::class, $offer);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($offer, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($offer);

			$offer->setMainPicture($mainPicture = $offer->getPictures()->first() ? $mainPicture = $offer->getPictures()->first() : null);
			if ($offer->getUser() == $this->getUser()) {
				$offer->setUpdatedAt(new \DateTime());
			}

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($offer, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.form.alert.update_success', array( '%title%' => $offer->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(OfferType::class, $offer);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'offer'        => $offer,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($offer),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_offer_delete")
	 */
	public function delete($id) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertDeletable($offer, true);

		// Delete
		$offerManager = $this->get(OfferManager::class);
		$offerManager->delete($offer);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.form.alert.delete_success', array( '%title%' => $offer->getTitle() )));

		return $this->redirect($this->generateUrl('core_offer_list'));
	}

	/**
	 * @Route("/{id}/chown", requirements={"id" = "\d+"}, name="core_offer_chown")
	 */
	public function chown(Request $request, $id) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertChownable($offer);

		$targetUser = $this->retrieveOwner($request);

		// Change owner
		$offerManager = $this->get(OfferManager::class);
		$offerManager->changeOwner($offer, $targetUser);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.form.alert.chown_success', array( '%title%' => $offer->getTitle() )));

		return $this->redirect($this->generateUrl('core_offer_show', array( 'id' => $offer->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_offer_widget")
	 * @Template("Offer/Offer/widget-xhr.html.twig")
	 */
	public function widget($id) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertShowable($offer, true);

		return array(
			'offer' => $offer,
		);
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_offer_location", defaults={"_format" = "json"})
	 * @Template("Offer/Offer/location.geojson.twig")
	 */
	public function location($id) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertShowable($offer);

		$features = array();
		if (!is_null($offer->getLongitude()) && !is_null($offer->getLatitude())) {
			$properties = array(
				'color'   => array( 1 => 'blue', 2 => 'green' )[$offer->getKind()],
				'cardUrl' => $this->generateUrl('core_offer_card', array('id' => $offer->getId())),
			);
			$gerometry = new \GeoJson\Geometry\Point($offer->getGeoPoint());
			$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
		}

		$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
		$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

		return array(
			'collection' => $collection,
		);
	}

	/**
	 * @Route("/{id}/card.xhr", name="core_offer_card")
	 * @Template("Offer/Offer/card-xhr.html.twig")
	 */
	public function card(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_offer_card)');
		}

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertShowable($offer);

		return array(
			'offer' => $offer,
		);
	}

	/**
	 * @Route("/{id}/message/new", name="core_offer_message_new")
	 */
	public function messageNew(Request $request, $id) {

		$offer = $this->retrievePublication($id, Offer::class);
		$this->assertShowable($offer, true);
		if ($offer->getUser() === $this->getUser()) {
			throw $this->createNotFoundException('Unable to send to yourself (core_offer_message_new)');
		}

		$translator = $this->get('translator');

		return $this->forward('App\Controller\Message\ThreadController::new', array(
			'recipientUsername' => $offer->getUser()->getUsernameCanonical(),
			'subject'           => $translator->trans('offer.offer.contact.subject', array('%TITLE%' => $offer->getTitle())),
			'message'           => $translator->trans('offer.offer.contact.body', array(
				'%TITLE%'     => $offer->getTitle(),
				'%URL%'       => $this->generateUrl('core_offer_show', array('id' => $offer->getSluggedId()), UrlGeneratorInterface::ABSOLUTE_URL),
				'%RECIPIENT%' => $offer->getUser()->getDisplayName(),
				'%SENDER%'    => $this->getUser()->getDisplayName()
			)),
			'alertTemplate'     => 'Offer/Offer/_alert-new-thread.part.html.twig',
		));
	}

	/**
	 * @Route("/", name="core_offer_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_offer_list_page")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_offer_list_geojson")
	 * @Template("Offer/Offer/list.html.twig")
	 */
	public function list(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_offer_list_page)');
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

					case 'kind':

						$filter = new \Elastica\Query\Term(array( 'kind' => $facet->value ));
						$filters[] = $filter;

						break;

					case 'category':

						$filter = new \Elastica\Query\Term(array( 'category' => $facet->value ));
						$filters[] = $filter;

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

					case 'sort-price':
						$sort = array( 'rawPrice' => array( 'order' => $searchUtils->getSorterOrder($facet, 'asc') ) );
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
			'offer_offer',
			\App\Entity\Offer\Offer::class,
			'core_offer_list_page'
		);

		$parameters = array_merge($searchParameters, array(
			'offers' => $searchParameters['entities'],
		));

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $offer) {
				$geoPoint = $offer->getGeoPoint();
				if (is_null($geoPoint)) {
					continue;
				}
				$properties = array(
					'color'   => array( 1 => 'blue', 2 => 'green' )[$offer->getKind()],
					'cardUrl' => $this->generateUrl('core_offer_card', array('id' => $offer->getId())),
				);
				$gerometry = new \GeoJson\Geometry\Point($geoPoint);
				$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
			}
			$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
			$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

			$parameters = array_merge($parameters, array(
				'collection' => $collection,
			));

			return $this->render('Offer/Offer/list-xhr.geojson.twig', $parameters);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Offer/Offer/list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateOfferCount() > 0) {

			$draftPath = $this->generateUrl('core_offer_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateOfferCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->trans('offer.offer.choice.draft_alert', array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_offer_show")
	 * @Template("Offer/Offer/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::class);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$offer = $offerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($offer)) {
			if ($response = $witnessManager->checkResponse(Offer::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}
		$this->assertShowable($offer);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($offer), PublicationListener::PUBLICATION_SHOWN);

		$explorableUtils = $this->get(ExplorableUtils::class);
		$userOffers = $explorableUtils->getPreviousAndNextPublishedUserExplorables($offer, $offerRepository, $offer->getUser()->getMeta()->getPublicOfferCount());
		$similarOffers = $explorableUtils->getSimilarExplorables($offer, 'offer_offer', Offer::class, $userOffers, 2, array( new \Elastica\Query\Term(array( 'kind' => $offer->getKind() )), new \Elastica\Query\Term(array( 'category' => $offer->getCategory() )) ));

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
		$followerUtils = $this->get(FollowerUtils::class);

		$hasMap = !is_null($offer->getLatitude()) && !is_null($offer->getLongitude());

		return array(
			'offer'             => $offer,
			'permissionContext' => $this->getPermissionContext($offer),
			'userOffers'        => $userOffers,
			'similarOffers'     => $similarOffers,
			'likeContext'       => $likableUtils->getLikeContext($offer, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($offer, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($offer),
			'collectionContext' => $collectionnableUtils->getCollectionContext($offer),
			'followerContext'   => $followerUtils->getFollowerContext($offer->getUser(), $this->getUser()),
			'hasMap'            => $hasMap,
		);
	}

	/**
	 * @Route("/{id}/admin/converttoquestion", requirements={"id" = "\d+"}, name="core_offer_offer_admin_converttoquestion")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_offer_offer_admin_converttoquestion)")
	 */
	public function adminConvertToQuestion($id) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::class);

		$offer = $offerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($offer)) {
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}

		// Convert
		$offerManager = $this->get(OfferManager::class);
		$question = $offerManager->convertToQuestion($offer);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.admin.alert.converttoquestion_success', array( '%title%' => $offer->getTitle() )));

		return $this->redirect($this->generateUrl('core_qa_question_show', array( 'id' => $question->getSluggedId() )));
	}

}