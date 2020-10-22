<?php

namespace Ladb\CoreBundle\Controller\Promotion;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Manager\Promotion\GraphicManager;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Entity\Promotion\Graphic;
use Ladb\CoreBundle\Form\Type\Promotion\GraphicType;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\GraphicUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;

/**
 * @Route("/promouvoir")
 */
class GraphicController extends AbstractController {

	/**
	 * @Route("/new", name="core_promotion_graphic_new")
	 * @Template("LadbCoreBundle:Promotion/Graphic:new.html.twig")
	 */
	public function newAction() {

		$graphic = new Graphic();
		$form = $this->createForm(GraphicType::class, $graphic);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($graphic),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_promotion_graphic_create")
	 * @Template("LadbCoreBundle:Promotion/Graphic:new.html.twig")
	 */
	public function createAction(Request $request) {

		$this->createLock('core_promotion_graphic_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$graphic = new Graphic();
		$form = $this->createForm(GraphicType::class, $graphic);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($graphic);

			$graphic->setUser($this->getUser());
			$graphic->setMainPicture($graphic->getResource()->getThumbnail());
			$this->getUser()->getMeta()->incrementPrivateGraphicCount();

			$om->persist($graphic);
			$om->flush();

			// Create zip archive after inserting graphic into database to be sure we have an ID
			$graphicUtils = $this->get(GraphicUtils::NAME);
			$graphicUtils->createZipArchive($graphic);

			$om->flush();	// Resave to store file size

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($graphic));

			return $this->redirect($this->generateUrl('core_promotion_graphic_show', array('id' => $graphic->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'graphic'         => $graphic,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($graphic),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_promotion_graphic_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_promotion_graphic_unlock")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_promotion_graphic_lock or core_promotion_graphic_unlock)")
	 */
	public function lockUnlockAction($id, $lock) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);

		$graphic = $graphicRepository->findOneById($id);
		if (is_null($graphic)) {
			throw $this->createNotFoundException('Unable to find Graphic entity (id='.$id.').');
		}
		if ($graphic->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_promotion_graphic_lock or core_promotion_graphic_unlock)');
		}

		// Lock or Unlock
		$graphicManager = $this->get(GraphicManager::NAME);
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
	public function publishAction($id) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);

		$graphic = $graphicRepository->findOneByIdJoinedOnUser($id);
		if (is_null($graphic)) {
			throw $this->createNotFoundException('Unable to find Graphic entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $graphic->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_promotion_graphic_publish)');
		}
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not emailConfirmed (core_promotion_graphic_publish)');
		}
		if ($graphic->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_promotion_graphic_publish)');
		}
		if ($graphic->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked (core_promotion_graphic_publish)');
		}

		// Publish
		$graphicManager = $this->get(GraphicManager::NAME);
		$graphicManager->publish($graphic);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('promotion.graphic.form.alert.publish_success', array( '%title%' => $graphic->getTitle() )));

		return $this->redirect($this->generateUrl('core_promotion_graphic_show', array( 'id' => $graphic->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_promotion_graphic_unpublish")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_promotion_graphic_unpublish)")
	 */
	public function unpublishAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);

		$graphic = $graphicRepository->findOneByIdJoinedOnUser($id);
		if (is_null($graphic)) {
			throw $this->createNotFoundException('Unable to find Graphic entity (id='.$id.').');
		}
		if ($graphic->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_promotion_graphic_unpublish)');
		}

		// Unpublish
		$graphicManager = $this->get(GraphicManager::NAME);
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
	 * @Template("LadbCoreBundle:Promotion/Graphic:edit.html.twig")
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);

		$graphic = $graphicRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($graphic)) {
			throw $this->createNotFoundException('Unable to find Graphic entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $graphic->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_promotion_graphic_edit)');
		}

		$form = $this->createForm(GraphicType::class, $graphic);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'graphic'         => $graphic,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($graphic),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_promotion_graphic_update")
	 * @Template("LadbCoreBundle:Promotion/Graphic:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);

		$graphic = $graphicRepository->findOneByIdJoinedOnUser($id);
		if (is_null($graphic)) {
			throw $this->createNotFoundException('Unable to find Graphic entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $graphic->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_promotion_graphic_update)');
		}

		$picturedUtils = $this->get(PicturedUtils::NAME);
		$picturedUtils->resetPictures($graphic); // Reset pictures array to consider form pictures order

		$previouslyUsedTags = $graphic->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(GraphicType::class, $graphic);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($graphic);

			$graphicUtils = $this->get(GraphicUtils::NAME);
			$graphicUtils->createZipArchive($graphic);

			$graphic->setMainPicture($graphic->getResource()->getThumbnail());
			if ($graphic->getUser() == $this->getUser()) {
				$graphic->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($graphic, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('promotion.graphic.form.alert.update_success', array( '%title%' => $graphic->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(GraphicType::class, $graphic);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'graphic'         => $graphic,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($graphic),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_promotion_graphic_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);

		$graphic = $graphicRepository->findOneByIdJoinedOnUser($id);
		if (is_null($graphic)) {
			throw $this->createNotFoundException('Unable to find Graphic entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !($graphic->getIsDraft() === true && $graphic->getUser()->getId() == $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_promotion_graphic_delete)');
		}

		// Delete
		$graphicManager = $this->get(GraphicManager::NAME);
		$graphicManager->delete($graphic);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('promotion.graphic.form.alert.delete_success', array( '%title%' => $graphic->getTitle() )));

		if ($graphic->getIsDraft()) {
			return $this->redirect($this->generateUrl('core_user_show_graphics', array( 'username' => $this->getUser()->getUsernameCanonical() )));
		}
		return $this->redirect($this->generateUrl('core_promotion_graphic_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_promotion_graphic_widget")
	 * @Template("LadbCoreBundle:Promotion/Graphic:widget-xhr.html.twig")
	 */
	public function widgetAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);

		$id = intval($id);

		$graphic = $graphicRepository->findOneById($id);
		if (is_null($graphic)) {
			throw $this->createNotFoundException('Unable to graphic Graphic entity (id='.$id.').');
		}
		if ($graphic->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_graphic_widget)');
		}

		return array(
			'graphic' => $graphic,
		);
	}

	/**
	 * @Route("/{id}/download", requirements={"id" = "\d+"}, name="core_promotion_graphic_download")
	 */
	public function downloadAction($id) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);

		$graphic = $graphicRepository->findOneById($id);
		if (is_null($graphic)) {
			throw $this->createNotFoundException('Unable to find Graphic entity (id='.$id.').');
		}
		if ($graphic->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $graphic->getUser()->getId() != $this->getUser()->getId())) {
				throw $this->createNotFoundException('Not allowed (core_promotion_graphic_download)');
			}
		}

		$graphicUtils = $this->get(GraphicUtils::NAME);
		$zipAbsolutePath = $graphicUtils->getZipAbsolutePath($graphic);
		if (!file_exists($zipAbsolutePath)) {
			if (!$graphicUtils->createZipArchive($graphic)) {
				throw $this->createNotFoundException('Zip archive not found (core_promotion_graphic_download)');
			}
		}

		$graphic->incrementDownloadCount(1);

		$om->flush();

		// Update index
		$searchUtils = $this->get(SearchUtils::NAME);
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
	 * @Template("LadbCoreBundle:Promotion/Graphic:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

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
			'fos_elastica.index.ladb.promotion_graphic',
			\Ladb\CoreBundle\Entity\Promotion\Graphic::CLASS_NAME,
			'core_promotion_graphic_list_page',
			$routeParameters
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		$parameters = array_merge($searchParameters, array(
			'graphics'        => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Promotion/Graphic:list-xhr.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateGraphicCount() > 0) {

			$draftPath = $this->generateUrl('core_promotion_graphic_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateGraphicCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->transchoice('promotion.graphic.choice.draft_alert', $draftCount, array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_promotion_graphic_show")
	 * @Template("LadbCoreBundle:Promotion/Graphic:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$graphic = $graphicRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($graphic)) {
			if ($response = $witnessManager->checkResponse(Graphic::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Graphic entity (id='.$id.').');
		}
		if ($graphic->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $graphic->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Graphic::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_promotion_graphic_show)');
			}
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($graphic));

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userGraphics = $explorableUtils->getPreviousAndNextPublishedUserExplorables($graphic, $graphicRepository, $graphic->getUser()->getMeta()->getPublicGraphicCount());
		$similarGraphics = $explorableUtils->getSimilarExplorables($graphic, 'fos_elastica.index.ladb.promotion_graphic', Graphic::CLASS_NAME, $userGraphics);

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'graphic'           => $graphic,
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