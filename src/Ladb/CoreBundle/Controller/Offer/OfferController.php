<?php

namespace Ladb\CoreBundle\Controller\Offer;

use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Form\Type\Offer\OfferType;
use Ladb\CoreBundle\Entity\Offer\Offer;
use Ladb\CoreBundle\Entity\Offer\Content\Gallery;
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
use Ladb\CoreBundle\Manager\Offer\OfferManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\OfferUtils;
use Ladb\CoreBundle\Utils\JoinableUtils;

/**
 * @Route("/petites-annonces")
 */
class OfferController extends Controller {

	/**
	 * @Route("/new", name="core_offer_new")
	 * @Template("LadbCoreBundle:Offer/Offer:new.html.twig")
	 */
	public function newAction() {

		$offer = new Offer();
		$offer->addBodyBlock(new \Ladb\CoreBundle\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(OfferType::class, $offer);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($offer),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_offer_create")
	 * @Template("LadbCoreBundle:Offer/Offer:new.html.twig")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$offer = new Offer();
		$form = $this->createForm(OfferType::class, $offer);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($offer);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($offer);

			$offer->setUser($this->getUser());
			$this->getUser()->getMeta()->incrementPrivateOfferCount();

			$om->persist($offer);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($offer));

			return $this->redirect($this->generateUrl('core_offer_show', array('id' => $offer->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'offer'        => $offer,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($offer),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_offer_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_offer_unlock")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_offer_lock)")
	 */
	public function lockUnlockAction($id, $lock) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::CLASS_NAME);

		$offer = $offerRepository->findOneById($id);
		if (is_null($offer)) {
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}
		if ($offer->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_offer_lock or core_offer_unlock)');
		}

		// Lock or Unlock
		$offerManager = $this->get(OfferManager::NAME);
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
	public function publishAction($id) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::CLASS_NAME);

		$offer = $offerRepository->findOneByIdJoinedOnUser($id);
		if (is_null($offer)) {
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $offer->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_offer_publish)');
		}
		if ($offer->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_offer_publish)');
		}
		if ($offer->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked (core_offer_publish)');
		}

		// Publish
		$offerManager = $this->get(OfferManager::NAME);
		$offerManager->publish($offer);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.form.alert.publish_success', array( '%title%' => $offer->getTitle() )));

		return $this->redirect($this->generateUrl('core_offer_show', array( 'id' => $offer->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_offer_unpublish")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_offer_unpublish)")
	 */
	public function unpublishAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::CLASS_NAME);

		$offer = $offerRepository->findOneByIdJoinedOnUser($id);
		if (is_null($offer)) {
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}
		if ($offer->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_offer_unpublish)');
		}

		// Unpublish
		$offerManager = $this->get(OfferManager::NAME);
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
	 * @Template("LadbCoreBundle:Offer/Offer:edit.html.twig")
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::CLASS_NAME);

		$offer = $offerRepository->findOneById($id);
		if (is_null($offer)) {
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $offer->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_offer_edit)');
		}

		$form = $this->createForm(OfferType::class, $offer);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'offer'        => $offer,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($offer),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_offer_update")
	 * @Template("LadbCoreBundle:Offer/Offer:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::CLASS_NAME);

		$offer = $offerRepository->findOneById($id);
		if (is_null($offer)) {
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $offer->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_offer_update)');
		}

		if ($offer->getContent() instanceof Gallery) {
			$picturedUtils = $this->get(PicturedUtils::NAME);
			$picturedUtils->resetPictures($offer->getContent()); // Reset pictures array to consider form pictures order
		}

		$originalBodyBlocks = $offer->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $offer->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(OfferType::class, $offer);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($offer, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($offer);

			if ($offer->getUser()->getId() == $this->getUser()->getId()) {
				$offer->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($offer, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.form.alert.update_success', array( '%title%' => $offer->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(OfferType::class, $offer);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'offer'        => $offer,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($offer),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_offer_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::CLASS_NAME);

		$offer = $offerRepository->findOneById($id);
		if (is_null($offer)) {
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !($offer->getIsDraft() === true && $offer->getUser()->getId() == $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_offer_delete)');
		}

		// Delete
		$offerManager = $this->get(OfferManager::NAME);
		$offerManager->delete($offer);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('offer.offer.form.alert.delete_success', array( '%title%' => $offer->getTitle() )));

		return $this->redirect($this->generateUrl('core_offer_list'));
	}

	/**
	 * @Route("/{id}/location.geojson", name="core_offer_location", defaults={"_format" = "json"})
	 * @Template("LadbCoreBundle:Offer/Offer:location.geojson.twig")
	 */
	public function locationAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::CLASS_NAME);

		$id = intval($id);

		$offer = $offerRepository->findOneById($id);
		if (is_null($offer)) {
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}
		if ($offer->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_offer_location)');
		}

		$features = array();
		if (!is_null($offer->getLongitude()) && !is_null($offer->getLatitude())) {
			$properties = array(
				'type' => 0,
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
	 * @Route("/", name="core_offer_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_offer_list_page")
	 * @Template("LadbCoreBundle:Offer/Offer:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_offer_list_page)');
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
									->addFilter(new \Elastica\Query\Range('visibility', array( 'lt' => HiddableInterface::VISIBILITY_PUBLIC )))
								;

							} else {

								$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							}

							$filters[] = $filter;

							$couldUseDefaultSort = true;

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
							$filter->setFields(array( 'title^100', 'body', 'tags.label' ));
							$filters[] = $filter;

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
			'fos_elastica.index.ladb.offer_offer',
			\Ladb\CoreBundle\Entity\Offer\Offer::CLASS_NAME,
			'core_offer_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		$parameters = array_merge($searchParameters, array(
			'offers' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Offer/Offer:list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateOfferCount() > 0) {

			$draftPath = $this->generateUrl('core_offer_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateOfferCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->transchoice('offer.offer.choice.draft_alert', $draftCount, array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_offer_show")
	 * @Template("LadbCoreBundle:Offer/Offer:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$offer = $offerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($offer)) {
			if ($response = $witnessManager->checkResponse(Offer::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Offer entity (id='.$id.').');
		}
		if ($offer->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $offer->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Offer::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_offer_show)');
			}
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($offer));

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userOffers = $explorableUtils->getPreviousAndNextPublishedUserExplorables($offer, $offerRepository, $offer->getUser()->getMeta()->getPublicOfferCount());
		$similarOffers = $explorableUtils->getSimilarExplorables($offer, 'fos_elastica.index.ladb.offer_offer', Offer::CLASS_NAME, $userOffers);

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		$hasMap = !is_null($offer->getLatitude()) && !is_null($offer->getLongitude());

		return array(
			'offer'           => $offer,
			'userOffers'      => $userOffers,
			'similarOffers'   => $similarOffers,
			'likeContext'     => $likableUtils->getLikeContext($offer, $this->getUser()),
			'watchContext'    => $watchableUtils->getWatchContext($offer, $this->getUser()),
			'commentContext'  => $commentableUtils->getCommentContext($offer),
			'followerContext' => $followerUtils->getFollowerContext($offer->getUser(), $this->getUser()),
			'hasMap'          => $hasMap,
		);
	}

}