<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Manager\Knowledge\WoodManager;
use Ladb\CoreBundle\Manager\WitnessManager;
use Ladb\CoreBundle\Manager\Wonder\WorkshopManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Wonder\Workshop;
use Ladb\CoreBundle\Form\Type\Wonder\WorkshopType;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\ReportableUtils;
use Ladb\CoreBundle\Utils\PublicationUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Utils\EmbeddableUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;

/**
 * @Route("/ateliers")
 */
class WorkshopController extends Controller {

	/**
	 * @Route("/new", name="core_workshop_new")
	 * @Template()
	 */
	public function newAction() {

		$workshop = new Workshop();
		$workshop->addBodyBlock(new \Ladb\CoreBundle\Entity\Block\Text());	// Add a default Text body block
		$form = $this->createForm(WorkshopType::class, $workshop);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
		);
	}

	/**
	 * @Route("/create", name="core_workshop_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Workshop:new.html.twig")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$workshop = new Workshop();
		$form = $this->createForm(WorkshopType::class, $workshop);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($workshop);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($workshop);

			$workshop->setUser($this->getUser());
			$workshop->setMainPicture($workshop->getPictures()->first());
			$this->getUser()->incrementDraftWorkshopCount();

			$om->persist($workshop);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($workshop));

			return $this->redirect($this->generateUrl('core_workshop_show', array('id' => $workshop->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'workshop'     => $workshop,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_workshop_publish")
	 */
	public function publishAction($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workshop->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_workshop_publish)');
		}
		if ($workshop->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_workshop_publish)');
		}

		// Publish
		$workshopManager = $this->get(WorkshopManager::NAME);
		$workshopManager->publish($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workshop.form.alert.publish_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_workshop_show', array( 'id' => $workshop->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_workshop_unpublish")
	 */
	public function unpublishAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_workshop_unpublish)');
		}
		if ($workshop->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_workshop_unpublish)');
		}

		// Unpublish
		$workshopManager = $this->get(WorkshopManager::NAME);
		$workshopManager->unpublish($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workshop.form.alert.unpublish_success', array( '%title%' => $workshop->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_workshop_edit")
	 * @Template()
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workshop->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_workshop_edit)');
		}

		$form = $this->createForm(WorkshopType::class, $workshop);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'workshop'     => $workshop,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, name="core_workshop_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Workshop:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workshop->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_workshop_update)');
		}

		$originalBodyBlocks = $workshop->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $workshop->getTags()->toArray();	// Need to be an array to copy values

		$picturedUtils = $this->get(PicturedUtils::NAME);
		$picturedUtils->resetPictures($workshop); // Reset pictures array to consider form pictures order

		$workshop->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(WorkshopType::class, $workshop);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($workshop, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($workshop);

			$embaddableUtils = $this->get(EmbeddableUtils::NAME);
			$embaddableUtils->resetSticker($workshop);

			$workshop->setMainPicture($workshop->getPictures()->first());
			if ($workshop->getUser()->getId() == $this->getUser()->getId()) {
				$workshop->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($workshop, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workshop.form.alert.update_success', array( '%title%' => $workshop->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(WorkshopType::class, $workshop);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'workshop'     => $workshop,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workshop),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_workshop_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !($workshop->getIsDraft() === true && $workshop->getUser()->getId() == $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_workshop_delete)');
		}

		// Delete
		$workshopManager = $this->get(WorkshopManager::NAME);
		$workshopManager->delete($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workshop.form.alert.delete_success', array( '%title%' => $workshop->getTitle() )));

		if ($workshop->getIsDraft()) {
			return $this->redirect($this->generateUrl('core_user_show_workshops', array( 'username' => $this->getUser()->getUsernameCanonical() )));
		}
		return $this->redirect($this->generateUrl('core_workshop_list'));
	}

	/**
	 * @Route("/{id}/plans", requirements={"id" = "\d+"}, name="core_workshop_plans")
	 * @Route("/{id}/plans/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workshop_plans_filter")
	 * @Route("/{id}/plans/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_plans_filter_page")
	 * @Template()
	 */
	public function plansAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Plans

		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedByWorkshop($workshop, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workshop_plans_filter_page', array( 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'plans'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workshop' => $workshop,
		));
	}

	/**
	 * @Route("/{id}/projets", requirements={"id" = "\d+"}, name="core_workshop_projects")
	 * @Template()
	 */
	public function projectsAction($id) {
		return $this->redirect($this->generateUrl('core_workshop_howtos', array( 'id' => $id )));
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_workshop_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workshop_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_howtos_filter_page")
	 * @Template()
	 */
	public function howtosAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneByIdJoinedOnUser($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $howtoRepository->findPaginedByWorkshop($workshop, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workshop_howtos_filter_page', array( 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'howtos'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workshop' => $workshop,
		));
	}

	/**
	 * @Route("/{id}/sticker.png", requirements={"id" = "\d+"}, name="core_workshop_sticker")
	 */
	public function stickerAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_workshop_sticker)');
		}

		$sticker = $workshop->getSticker();
		if (is_null($sticker)) {
			$embeddableUtils = $this->get(EmbeddableUtils::NAME);
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
	 * @Route("/{id}/location.geojson", name="core_workshop_location", defaults={"_format" = "json"})
	 * @Template("LadbCoreBundle:Workshop:location.geojson.twig")
	 */
	public function locationAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_workshop_location)');
		}

		$features = array();
		if (!is_null($workshop->getLongitude()) && !is_null($workshop->getLatitude())) {
			$properties = array(
				'type' => 0,
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
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_workshop_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_workshop_list_filter_page")
	 * @Template()
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_workshop_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_workshop_list_page")
	 * @Route(".geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_workshop_list_geojson")
	 * @Template()
	 */
	public function listAction(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				switch ($facet->name) {

					case 'tag':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'tags.name' ));
						$filters[] = $filter;

						break;

					case 'author':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'user.displayname', 'user.fullname', 'user.username'  ));
						$filters[] = $filter;

						break;

					case 'license':

						$filter = new \Elastica\Query\MatchPhrase('license.strippedname', $facet->value);
						$filters[] = $filter;

						break;

					case 'content-plans':

						$filter = new \Elastica\Query\Range('planCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-howtos':

						$filter = new \Elastica\Query\Range('howtoCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-videos':

						$filter = new \Elastica\Query\Range('bodyBlockVideoCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'around':

						if (isset($facet->value)) {
							$filter = new \Elastica\Query\Filtered(null, new \Elastica\Filter\GeoDistance('geoPoint', $facet->value, '100km'));
							$filters[] = $filter;
						}

						break;

					case 'sort':

						switch ($facet->value) {

							case 'recent':
								$sort = array( 'changedAt' => array( 'order' => 'desc' ) );
								break;

							case 'popular-views':
								$sort = array( 'viewCount' => array( 'order' => 'desc' ) );
								break;

							case 'popular-likes':
								$sort = array( 'likeCount' => array( 'order' => 'desc' ) );
								break;

							case 'popular-comments':
								$sort = array( 'commentCount' => array( 'order' => 'desc' ) );
								break;

							case 'largest':
								$sort = array( 'area' => array( 'order' => 'desc' ) );
								break;

						}

						break;

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'title', 'body', 'tags.name' ));
							$filters[] = $filter;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			'fos_elastica.index.ladb.workshop',
			\Ladb\CoreBundle\Entity\Wonder\Workshop::CLASS_NAME,
			'core_workshop_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities']));

		$parameters = array_merge($searchParameters, array(
			'workshops' => $searchParameters['entities'],
		));

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $workshop) {
				if (is_null($workshop->getLongitude()) || is_null($workshop->getLatitude())) {
					continue;
				}
				$properties = array(
					'type'    => 0,
					'cardUrl' => $this->generateUrl('core_workshop_card', array( 'id' => $workshop->getId() )),
				);
				$gerometry = new \GeoJson\Geometry\Point($workshop->getGeoPoint());
				$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
			}
			$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
			$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

			$parameters = array_merge($parameters, array(
				'collection' => $collection,
			));

			return $this->render('LadbCoreBundle:Workshop:list-xhr.geojson.twig', $parameters);
		}

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Workshop:list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getDraftWorkshopCount() > 0) {

			$draftPath = $this->generateUrl('core_user_show_workshops_filter', array( 'username' => $this->getUser()->getUsernameCanonical(), 'filter' => 'draft' ));
			$draftCount = $this->getUser()->getDraftWorkshopCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->transchoice('workshop.choice.draft_alert', $draftCount, array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}/card.xhr", name="core_workshop_card")
	 * @Template("LadbCoreBundle:Workshop:card-xhr.html.twig")
	 */
	public function cardAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity.');
		}

		return array(
			'workshop' => $workshop,
		);
	}

	/**
	 * @Route("/{id}.html", name="core_workshop_show")
	 * @Template()
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$workshop = $workshopRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($workshop)) {
			if ($response = $witnessManager->checkResponse(Workshop::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}
		if ($workshop->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $workshop->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Workshop::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_workshop_show)');
			}
		}

		$embaddableUtils = $this->get(EmbeddableUtils::NAME);
		$referral = $embaddableUtils->processReferer($workshop, $request);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($workshop));

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userWorkshops = $explorableUtils->getPreviousAndNextPublishedUserExplorables($workshop, $workshopRepository, $workshop->getUser()->getPublishedWorkshopCount());
		$similarWorkshops = $explorableUtils->getSimilarExplorables($workshop, 'fos_elastica.index.ladb.workshop', Workshop::CLASS_NAME, $userWorkshops);

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'workshop'         => $workshop,
			'userWorkshops'    => $userWorkshops,
			'similarWorkshops' => $similarWorkshops,
			'likeContext'      => $likableUtils->getLikeContext($workshop, $this->getUser()),
			'watchContext'     => $watchableUtils->getWatchContext($workshop, $this->getUser()),
			'commentContext'   => $commentableUtils->getCommentContext($workshop),
			'followerContext'  => $followerUtils->getFollowerContext($workshop->getUser(), $this->getUser()),
			'referral'         => $referral,
			'hasMap'           => !is_null($workshop->getLatitude()) && !is_null($workshop->getLongitude()),
		);
	}

	/**
	 * @Route("/{id}/admin/converttohowto", requirements={"id" = "\d+"}, name="core_workshop_admin_converttohowto")
	 * @Template()
	 */
	public function adminConvertToHowtoAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);

		$workshop = $workshopRepository->findOneById($id);
		if (is_null($workshop)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Convert
		$workshopManager = $this->get(WorkshopManager::NAME);
		$howto = $workshopManager->convertToHowto($workshop);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workshop.admin.alert.converttohowto_success', array( '%title%' => $workshop->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

}
