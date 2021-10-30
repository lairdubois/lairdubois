<?php

namespace App\Controller\Promotion;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Promotion\Graphic;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Form\Type\Promotion\GraphicType;
use App\Manager\Core\WitnessManager;
use App\Manager\Promotion\GraphicManager;
use App\Model\HiddableInterface;
use App\Utils\CollectionnableUtils;
use App\Utils\CommentableUtils;
use App\Utils\ExplorableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\FollowerUtils;
use App\Utils\GraphicUtils;
use App\Utils\LikableUtils;
use App\Utils\PicturedUtils;
use App\Utils\SearchUtils;
use App\Utils\TagUtils;
use App\Utils\WatchableUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/promouvoir")
 */
class GraphicController extends AbstractController {

	use PublicationControllerTrait;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.WitnessManager::class,
            '?'.GraphicManager::class,
            '?'.CollectionnableUtils::class,
            '?'.CommentableUtils::class,
            '?'.ExplorableUtils::class,
            '?'.FieldPreprocessorUtils::class,
            '?'.FollowerUtils::class,
            '?'.GraphicUtils::class,
            '?'.LikableUtils::class,
            '?'.PicturedUtils::class,
            '?'.SearchUtils::class,
            '?'.TagUtils::class,
            '?'.WatchableUtils::class,
        ));
    }

	/**
	 * @Route("/new", name="core_promotion_graphic_new")
	 * @Template("Promotion/Graphic/new.html.twig")
	 */
	public function new(Request $request) {

		$graphic = new Graphic();
		$form = $this->createForm(GraphicType::class, $graphic);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'owner'        => $this->retrieveOwner($request),
			'tagProposals' => $tagUtils->getProposals($graphic),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_promotion_graphic_create")
	 * @Template("Promotion/Graphic/new.html.twig")
	 */
	public function create(Request $request) {

		$owner = $this->retrieveOwner($request);

		$this->createLock('core_promotion_graphic_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$graphic = new Graphic();
		$form = $this->createForm(GraphicType::class, $graphic);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($graphic);

			$graphic->setUser($owner);
			$graphic->setMainPicture($graphic->getResource()->getThumbnail());
			$owner->getMeta()->incrementPrivateGraphicCount();

			$om->persist($graphic);
			$om->flush();

			// Create zip archive after inserting graphic into database to be sure we have an ID
			$graphicUtils = $this->get(GraphicUtils::class);
			$graphicUtils->createZipArchive($graphic);

			$om->flush();	// Resave to store file size

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($graphic), PublicationListener::PUBLICATION_CREATED);

			return $this->redirect($this->generateUrl('core_promotion_graphic_show', array('id' => $graphic->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'graphic'         => $graphic,
			'form'         => $form->createView(),
			'owner'        => $owner,
			'tagProposals' => $tagUtils->getProposals($graphic),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_promotion_graphic_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_promotion_graphic_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$graphic = $this->retrievePublication($id, Graphic::CLASS_NAME);
		$this->assertLockUnlockable($graphic, $lock);

		// Lock or Unlock
		$graphicManager = $this->get(GraphicManager::class);
		if ($lock) {
			$graphicManager->lock($graphic);
		} else {
			$graphicManager->unlock($graphic);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('promotion.graphic.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $graphic->getTitle() )));

		return $this->redirect($this->generateUrl('core_promotion_graphic_show', array( 'id' => $graphic->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_promotion_graphic_publish")
	 */
	public function publish($id) {

		$graphic = $this->retrievePublication($id, Graphic::CLASS_NAME);
		$this->assertPublishable($graphic);

		// Publish
		$graphicManager = $this->get(GraphicManager::class);
		$graphicManager->publish($graphic);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('promotion.graphic.form.alert.publish_success', array( '%title%' => $graphic->getTitle() )));

		return $this->redirect($this->generateUrl('core_promotion_graphic_show', array( 'id' => $graphic->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_promotion_graphic_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$graphic = $this->retrievePublication($id, Graphic::CLASS_NAME);
		$this->assertUnpublishable($graphic);

		// Unpublish
		$graphicManager = $this->get(GraphicManager::class);
		$graphicManager->unpublish($graphic);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('promotion.graphic.form.alert.unpublish_success', array( '%title%' => $graphic->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_promotion_graphic_edit")
	 * @Template("Promotion/Graphic/edit.html.twig")
	 */
	public function edit($id) {

		$graphic = $this->retrievePublication($id, Graphic::CLASS_NAME);
		$this->assertEditabable($graphic);

		$form = $this->createForm(GraphicType::class, $graphic);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'graphic'         => $graphic,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($graphic),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_promotion_graphic_update")
	 * @Template("Promotion/Graphic/edit.html.twig")
	 */
	public function update(Request $request, $id) {

		$graphic = $this->retrievePublication($id, Graphic::CLASS_NAME);
		$this->assertEditabable($graphic);

		$picturedUtils = $this->get(PicturedUtils::class);
		$picturedUtils->resetPictures($graphic); // Reset pictures array to consider form pictures order

		$previouslyUsedTags = $graphic->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(GraphicType::class, $graphic);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($graphic);

			$graphicUtils = $this->get(GraphicUtils::class);
			$graphicUtils->createZipArchive($graphic);

			$graphic->setMainPicture($graphic->getResource()->getThumbnail());
			if ($graphic->getUser() == $this->getUser()) {
				$graphic->setUpdatedAt(new \DateTime());
			}

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($graphic, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('promotion.graphic.form.alert.update_success', array( '%title%' => $graphic->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(GraphicType::class, $graphic);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'graphic'         => $graphic,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($graphic),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_promotion_graphic_delete")
	 */
	public function delete($id) {

		$graphic = $this->retrievePublication($id, Graphic::CLASS_NAME);
		$this->assertDeletable($graphic);

		// Delete
		$graphicManager = $this->get(GraphicManager::class);
		$graphicManager->delete($graphic);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('promotion.graphic.form.alert.delete_success', array( '%title%' => $graphic->getTitle() )));

		return $this->redirect($this->generateUrl('core_promotion_graphic_list'));
	}

	/**
	 * @Route("/{id}/chown", requirements={"id" = "\d+"}, name="core_promotion_graphic_chown")
	 */
	public function chown(Request $request, $id) {

		$graphic = $this->retrievePublication($id, Graphic::CLASS_NAME);
		$this->assertChownable($graphic);

		$targetUser = $this->retrieveOwner($request);

		// Change owner
		$graphicManager = $this->get(GraphicManager::class);
		$graphicManager->changeOwner($graphic, $targetUser);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('promotion.graphic.form.alert.chown_success', array( '%title%' => $graphic->getTitle() )));

		return $this->redirect($this->generateUrl('core_promotion_graphic_show', array( 'id' => $graphic->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_promotion_graphic_widget")
	 * @Template("Promotion/Graphic/widget-xhr.html.twig")
	 */
	public function widget($id) {

		$graphic = $this->retrievePublication($id, Graphic::CLASS_NAME);
		$this->assertShowable($graphic, true);

		return array(
			'graphic' => $graphic,
		);
	}

	/**
	 * @Route("/{id}/download", requirements={"id" = "\d+"}, name="core_promotion_graphic_download")
	 */
	public function download($id) {
		$om = $this->getDoctrine()->getManager();

		$graphic = $this->retrievePublication($id, Graphic::CLASS_NAME);
		$this->assertShowable($graphic);

		$graphicUtils = $this->get(GraphicUtils::class);
		$zipAbsolutePath = $graphicUtils->getZipAbsolutePath($graphic);
		if (!file_exists($zipAbsolutePath)) {
			if (!$graphicUtils->createZipArchive($graphic)) {
				throw $this->createNotFoundException('Zip archive not found (core_promotion_graphic_download)');
			}
		}

		$graphic->incrementDownloadCount(1);

		$om->flush();

		// Update index
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->replaceEntityInIndex($graphic);

		$content = file_get_contents($zipAbsolutePath);

		$response = new Response();
		$response->headers->set('Content-Type', 'mime/type');
		$response->headers->set('Content-Length', filesize($zipAbsolutePath));
		$response->headers->set('Content-Disposition', 'attachment;filename="lairdubois_'.$graphic->getUser()->getUsernameCanonical().'_'.$graphic->getSlug().'.zip"');
		$response->headers->set('Expires', 0);
		$response->headers->set('Cache-Control', 'no-cache, must-revalidate');
		$response->headers->set('Pragma', 'no-cache');

		$response->setContent($content);

		return $response;
	}

	/**
	 * @Route("/", name="core_promotion_graphic_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_promotion_graphic_list_page")
	 * @Template("Promotion/Graphic/list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_qa_question_list_page)');
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

					case 'license':

						$filter = new \Elastica\Query\Term([ 'license.strippedname' => [ 'value' => $facet->value, 'boost' => 1.0 ] ]);
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

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					case 'popular-downloads':
						$sort = array( 'downloadCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
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
			'promotion_graphic',
			\App\Entity\Promotion\Graphic::CLASS_NAME,
			'core_promotion_graphic_list_page',
			$routeParameters
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'graphics'        => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('Promotion/Graphic/list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateGraphicCount() > 0) {

			$draftPath = $this->generateUrl('core_promotion_graphic_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateGraphicCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->trans('promotion.graphic.choice.draft_alert', array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_promotion_graphic_show")
	 * @Template("Promotion/Graphic/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$graphic = $graphicRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($graphic)) {
			if ($response = $witnessManager->checkResponse(Graphic::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Graphic entity (id='.$id.').');
		}
		$this->assertShowable($graphic);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($graphic), PublicationListener::PUBLICATION_SHOWN);

		$explorableUtils = $this->get(ExplorableUtils::class);
		$userGraphics = $explorableUtils->getPreviousAndNextPublishedUserExplorables($graphic, $graphicRepository, $graphic->getUser()->getMeta()->getPublicGraphicCount());
		$similarGraphics = $explorableUtils->getSimilarExplorables($graphic, 'promotion_graphic', Graphic::CLASS_NAME, $userGraphics);

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
		$followerUtils = $this->get(FollowerUtils::class);

		return array(
			'graphic'           => $graphic,
			'permissionContext' => $this->getPermissionContext($graphic),
			'userGraphics'      => $userGraphics,
			'similarGraphics'   => $similarGraphics,
			'likeContext'       => $likableUtils->getLikeContext($graphic, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($graphic, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($graphic),
			'collectionContext' => $collectionnableUtils->getCollectionContext($graphic),
			'followerContext'   => $followerUtils->getFollowerContext($graphic->getUser(), $this->getUser()),
		);
	}

}