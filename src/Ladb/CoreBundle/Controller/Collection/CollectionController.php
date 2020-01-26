<?php

namespace Ladb\CoreBundle\Controller\Collection;

use Ladb\CoreBundle\Entity\Collection\Entry;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Entity\Collection\Collection;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Manager\Collection\CollectionManager;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Form\Type\Collection\CollectionType;

/**
 * @Route("/collections")
 */
class CollectionController extends AbstractCollectionBasedController {

	/**
	 * @Route("/new", name="core_collection_new")
	 * @Template("LadbCoreBundle:Collection:Collection/new.html.twig")
	 */
	public function newAction(Request $request) {

		$collection = new Collection();
		$form = $this->createForm(CollectionType::class, $collection);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($collection),
			'entityType'   => $request->get('entityType', null),
			'entityId'     => $request->get('entityId', null),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_collection_create")
	 * @Template("LadbCoreBundle:Collection:Collection/new.html.twig")
	 */
	public function createAction(Request $request) {

		$this->createLock('core_collection_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$collection = new Collection();
		$form = $this->createForm(CollectionType::class, $collection);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($collection);

			$collection->setUser($this->getUser());
			$this->getUser()->getMeta()->incrementPrivateCollectionCount();

			$om->persist($collection);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($collection));

			// Check auto add
			$entityType = $request->get('entityType', null);
			$entityId = $request->get('entityId', null);
			if (!is_null($entityType) && !is_null($entityId)) {

				// Retrieve related entity
				$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

				// Create entry
				$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
				try {
					$collectionnableUtils->createEntry($entity, $collection);
				} catch (\Exception $e) {
					throw $this->createNotFoundException($e->getMessage());
				}

				// Flashbag
				$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.auto_add_success', array( '%title%' => $entity->getTitle(), '%collection%' => $collection->getTitle() )));

				$typableUtils = $this->get(TypableUtils::NAME);
				return $this->redirect($typableUtils->getUrlAction($entity));
			}

			return $this->redirect($this->generateUrl('core_collection_show', array( 'id' => $collection->getId() )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'collection'   => $collection,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($collection),
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_collection_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_collection_unlock")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_collection_lock or core_collection_unlock)")
	 */
	public function lockUnlockAction($id, $lock) {

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if ($collection->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_collection_lock or core_collection_unlock)');
		}

		// Lock or Unlock
		$collectionManager = $this->get(CollectionManager::NAME);
		if ($lock) {
			$collectionManager->lock($collection);
		} else {
			$collectionManager->unlock($collection);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $collection->getTitle() )));

		return $this->redirect($this->generateUrl('core_collection_show', array( 'id' => $collection->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_collection_publish")
	 */
	public function publishAction($id) {

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_publish)');
		}
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not emailConfirmed (core_collection_publish)');
		}
		if ($collection->getIsPublic()) {
			throw $this->createNotFoundException('Already published (core_collection_publish)');
		}
		if ($collection->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked (core_collection_publish)');
		}

		// Publish
		$collectionManager = $this->get(CollectionManager::NAME);
		$collectionManager->publish($collection);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.publish_success', array( '%title%' => $collection->getTitle() )));

		return $this->redirect($this->generateUrl('core_collection_show', array( 'id' => $collection->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_collection_unpublish")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_collection_unpublish)")
	 */
	public function unpublishAction(Request $request, $id) {

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$collection->getIsPublic()) {
			throw $this->createNotFoundException('Already unpublished (core_collection_publish)');
		}

		// Unpublish
		$collectionManager = $this->get(CollectionManager::NAME);
		$collectionManager->unpublish($collection);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.unpublish_success', array( '%title%' => $collection->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_collection_edit")
	 * @Template("LadbCoreBundle:Collection:Collection/edit.html.twig")
	 */
	public function editAction($id) {

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_edit)');
		}

		$form = $this->createForm(CollectionType::class, $collection);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'collection'   => $collection,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($collection),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_collection_update")
	 * @Template("LadbCoreBundle:Collection:Collection/edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_update)');
		}

		$previouslyUsedTags = $collection->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(CollectionType::class, $collection);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($collection);

			if ($collection->getUser()->getId() == $this->getUser()->getId()) {
				$collection->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($collection, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.update_success', array( '%title%' => $collection->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(CollectionType::class, $collection);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'collection'   => $collection,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($collection),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_collection_delete")
	 */
	public function deleteAction($id) {

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_delete)');
		}

		// Delete
		$collectionManager = $this->get(CollectionManager::NAME);
		$collectionManager->delete($collection);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.delete_success', array( '%title%' => $collection->getTitle() )));

		return $this->redirect($this->generateUrl('core_collection_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_collection_widget")
	 * @Template("LadbCoreBundle:Collection/Collection:widget-xhr.html.twig")
	 */
	public function widgetAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$collectionRepository = $om->getRepository(Collection::CLASS_NAME);

		$id = intval($id);

		$collection = $collectionRepository->findOneById($id);
		if (is_null($collection)) {
			throw $this->createNotFoundException('Unable to find Collection entity (id='.$id.').');
		}
		if (!$collection->getIsPublic()) {
			throw $this->createNotFoundException('Not allowed (core_collection_widget)');
		}

		return array(
			'collection' => $collection,
		);
	}

	/**
	 * @Route("/{id}/{entityType}/{entityId}/+", requirements={"id" = "\d+", "entityType" = "\d+", "entityId" = "\d+"}, name="core_collection_add")
	 */
	public function addAction($id, $entityType, $entityId) {

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_delete)');
		}

		// Delete
		$collectionManager = $this->get(CollectionManager::NAME);
		$collectionManager->delete($collection);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.delete_success', array( '%title%' => $collection->getTitle() )));

		return $this->redirect($this->generateUrl('core_collection_list'));
	}

	/**
	 * @Route("/{id}.html", name="core_collection_show")
	 * @Route("/{id}/tab/{entityType}", requirements={"id" = "\d+", "entityType" = "\d+"}, name="core_collection_show_type")
	 * @Route("/{id}/tab/{entityType}/{page}", requirements={"id" = "\d+", "entityType" = "\d+", "page" = "\d+"}, name="core_collection_show_type_page")
	 * @Template("LadbCoreBundle:Collection:Collection/showAbout.html.twig")
	 */
	public function showAction(Request $request, $id, $entityType = 0, $page = 0) {
		$witnessManager = $this->get(WitnessManager::NAME);

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$collection->getIsPublic()) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $collection->getUser() != $this->getUser())) {
				if ($response = $witnessManager->checkResponse(Collection::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_collection_show)');
			}
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($collection));

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		$parameters = array(
			'entityType'        => $entityType,
			'collection'        => $collection,
			'likeContext'       => $likableUtils->getLikeContext($collection, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($collection, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($collection),
			'collectionContext' => $collectionnableUtils->getCollectionContext($collection),
			'followerContext'   => $followerUtils->getFollowerContext($collection->getUser(), $this->getUser()),
		);

		if ($entityType > 0) {

			$om = $this->getDoctrine()->getManager();
			$entryRepository = $om->getRepository(Entry::CLASS_NAME);
			$paginatorUtils = $this->get(PaginatorUtils::NAME);
			$typableUtils = $this->get(TypableUtils::NAME);

			$offset = $paginatorUtils->computePaginatorOffset($page);
			$limit = $paginatorUtils->computePaginatorLimit($page);
			$paginator = $entryRepository->findPaginedByEntityTypeAndCollection($entityType, $collection, $offset, $limit);
			$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_collection_show_type_page', array( 'id' => $collection->getId(), 'entityType' => $entityType ), $page, $paginator->count());

			$entityIds = array();
			foreach ($paginator as $entry) {
				$entityIds[] = $entry->getEntityId();
			}
			$entities = $typableUtils->findTypables($entityType, $entityIds);

			$parameters = array_merge($parameters, array(
				'prevPageUrl' => $pageUrls->prev,
				'nextPageUrl' => $pageUrls->next,
				'entries'     => $paginator,
				'entities'    => $entities,
			));

			if ($request->isXmlHttpRequest()) {
				return $this->render('LadbCoreBundle:Collection:Collection/showEntities-xhr.html.twig', $parameters);
			}
			return $this->render('LadbCoreBundle:Collection:Collection/showEntities.html.twig', $parameters);

		}

		return $parameters;
	}

	/**
	 * @Route("/", name="core_collection_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_collection_list_page")
	 * @Template("LadbCoreBundle:Collection:Collection/list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_collection_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				switch ($facet->name) {

					// Filters /////

					case 'admin-all':
						if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {

							$filters[] = new \Elastica\Query\MatchAll();

							$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

							$noGlobalFilters = true;
						}
						break;

					case 'mine':

						if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

							$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							$filters[] = $filter;

						}

						break;

					case 'period':

						if ($facet->value == 'last7days') {

							$filters[] = new \Elastica\Query\Range('changedAt', array( 'gte' => 'now-7d/d' ));

						} elseif ($facet->value == 'last30days') {

							$filters[] = new \Elastica\Query\Range('changedAt', array( 'gte' => 'now-30d/d' ));

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
			'fos_elastica.index.ladb.collection_collection',
			\Ladb\CoreBundle\Entity\Collection\Collection::CLASS_NAME,
			'core_collection_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		$parameters = array_merge($searchParameters, array(
			'collections' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Collection:Collection/list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/available/{entityType}/{entityId}", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_collection_list_available")
	 * @Route("/available/{entityType}/{entityId}/{page}", requirements={"entityType" = "\d+", "entityId" = "\d+", "page" = "\d+"}, name="core_collection_list_available_page")
	 * @Template("LadbCoreBundle:Collection/Collection:list-available-xhr.html.twig")
	 */
	public function listAvailableAction($entityType, $entityId, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$collectionRepository = $om->getRepository(Collection::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		// Retrieve related entity
		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $collectionRepository->findPaginedByUser($this->getUser(), $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_collection_list_available_page', array( 'entityType' => $entityType, 'entityId' => $entityId ), $page, $paginator->count());

		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);

		// Compute entryContexts
		$entryContexts = array();
		foreach ($paginator as $collection) {
			$entryContexts[] = $collectionnableUtils->getEntryContext($collection, $entity);
		}

		$parameters = array(
			'prevPageUrl'   => $pageUrls->prev,
			'nextPageUrl'   => $pageUrls->next,
			'collections'   => $paginator,
			'entity'        => $entity,
			'entryContexts' => $entryContexts,
		);

		if ($page > 0) {
			return $this->render('LadbCoreBundle:Collection/Collection:list-available-n-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{entityType}/{entityId}", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_collection_list_byentity")
	 * @Route("/{entityType}/{entityId}/{page}", requirements={"entityType" = "\d+", "entityId" = "\d+", "page" = "\d+"}, name="core_collection_list_byentity_page")
	 * @Template("LadbCoreBundle:Collection/Collection:list-byentity.html.twig")
	 */
	public function listByEntityAction(Request $request, $entityType, $entityId, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$collectionRepository = $om->getRepository(Collection::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		// Retrieve related entity
		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		$offset = $paginatorUtils->computePaginatorOffset($page, 9, 5);
		$limit = $paginatorUtils->computePaginatorLimit($page, 9, 5);
		$paginator = $collectionRepository->findPaginedByEntity($entity, $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_collection_list_byentity_page', array( 'entityType' => $entityType, 'entityId' => $entityId ), $page, $paginator->count());

		$parameters = array(
			'prevPageUrl'   => $pageUrls->prev,
			'nextPageUrl'   => $pageUrls->next,
			'collections'   => $paginator,
			'entity'        => $entity,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Collection/Collection:list-byentity-n-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

}
