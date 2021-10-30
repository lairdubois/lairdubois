<?php

namespace App\Controller\Collection;

use App\Controller\PublicationControllerTrait;
use App\Entity\Collection\Collection;
use App\Entity\Collection\Entry;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Form\Type\Collection\CollectionType;
use App\Manager\Collection\CollectionManager;
use App\Manager\Core\WitnessManager;
use App\Model\HiddableInterface;
use App\Utils\CollectionnableUtils;
use App\Utils\CommentableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\FollowerUtils;
use App\Utils\LikableUtils;
use App\Utils\PaginatorUtils;
use App\Utils\SearchUtils;
use App\Utils\TagUtils;
use App\Utils\TypableUtils;
use App\Utils\WatchableUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/collections")
 */
class CollectionController extends AbstractCollectionBasedController {

	use PublicationControllerTrait;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.CollectionManager::class,
            '?'.WitnessManager::class,
            '?'.CollectionnableUtils::class,
            '?'.CommentableUtils::class,
            '?'.FieldPreprocessorUtils::class,
            '?'.FollowerUtils::class,
            '?'.LikableUtils::class,
            '?'.PaginatorUtils::class,
            '?'.SearchUtils::class,
            '?'.TagUtils::class,
            '?'.TypableUtils::class,
            '?'.WatchableUtils::class,
        ));
    }

	/**
	 * @Route("/new", name="core_collection_new")
	 * @Template("Collection/Collection/new.html.twig")
	 */
	public function new(Request $request) {

		$collection = new Collection();
		$form = $this->createForm(CollectionType::class, $collection);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($collection),
			'entityType'   => $request->get('entityType', null),
			'entityId'     => $request->get('entityId', null),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_collection_create")
	 * @Template("Collection/Collection/new.html.twig")
	 */
	public function create(Request $request) {

		$this->createLock('core_collection_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$collection = new Collection();
		$form = $this->createForm(CollectionType::class, $collection);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($collection);

			$collection->setUser($this->getUser());
			$this->getUser()->getMeta()->incrementPrivateCollectionCount();

			$om->persist($collection);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($collection), PublicationListener::PUBLICATION_CREATED);

			// Check auto add
			$entityType = $request->get('entityType', null);
			$entityId = $request->get('entityId', null);
			if (!is_null($entityType) && !is_null($entityId)) {

				// Retrieve related entity
				$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

				// Create entry
				$collectionnableUtils = $this->get(CollectionnableUtils::class);
				try {
					$collectionnableUtils->createEntry($entity, $collection);
				} catch (\Exception $e) {
					throw $this->createNotFoundException($e->getMessage());
				}

				// Flashbag
				$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.auto_add_success', array( '%title%' => $entity->getTitle(), '%collection%' => $collection->getTitle() )));

				$typableUtils = $this->get(TypableUtils::class);
				return $this->redirect($typableUtils->getUrlAction($entity));
			}

			return $this->redirect($this->generateUrl('core_collection_show', array( 'id' => $collection->getId() )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'collection'   => $collection,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($collection),
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_collection_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_collection_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$collection = $this->retrievePublication($id, Collection::class);
		$this->assertLockUnlockable($collection, $lock);

		if ($collection->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_collection_lock or core_collection_unlock)');
		}

		// Lock or Unlock
		$collectionManager = $this->get(CollectionManager::class);
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
	public function publish($id) {

		$collection = $this->retrievePublication($id, Collection::class);
		$this->assertPublishable($collection);

		// Publish
		$collectionManager = $this->get(CollectionManager::class);
		$collectionManager->publish($collection);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.publish_success', array( '%title%' => $collection->getTitle() )));

		return $this->redirect($this->generateUrl('core_collection_show', array( 'id' => $collection->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_collection_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$collection = $this->retrievePublication($id, Collection::class);
		$this->assertUnpublishable($collection);

		// Unpublish
		$collectionManager = $this->get(CollectionManager::class);
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
	 * @Template("Collection/Collection/edit.html.twig")
	 */
	public function edit($id) {

		$collection = $this->retrievePublication($id, Collection::class);
		$this->assertEditabable($collection);

		$form = $this->createForm(CollectionType::class, $collection);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'collection'   => $collection,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($collection),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_collection_update")
	 * @Template("Collection/Collection/edit.html.twig")
	 */
	public function update(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		$collection = $this->retrievePublication($id, Collection::class);
		$this->assertEditabable($collection);

		$previouslyUsedTags = $collection->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(CollectionType::class, $collection);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($collection);

			if ($collection->getUser()->getId() == $this->getUser()->getId()) {
				$collection->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($collection, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.update_success', array( '%title%' => $collection->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(CollectionType::class, $collection);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'collection'   => $collection,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($collection),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_collection_delete")
	 */
	public function delete($id) {

		$collection = $this->retrievePublication($id, Collection::class);
		$this->assertDeletable($collection);

		// Delete
		$collectionManager = $this->get(CollectionManager::class);
		$collectionManager->delete($collection);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.delete_success', array( '%title%' => $collection->getTitle() )));

		return $this->redirect($this->generateUrl('core_collection_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_collection_widget")
	 * @Template("Collection/Collection/widget-xhr.html.twig")
	 */
	public function widget(Request $request, $id) {

		$collection = $this->retrievePublication($id, Collection::class);
		$this->assertShowable($collection, true);

		return array(
			'collection' => $collection,
		);
	}

	/**
	 * @Route("/{id}/{entityType}/{entityId}/+", requirements={"id" = "\d+", "entityType" = "\d+", "entityId" = "\d+"}, name="core_collection_add")
	 */
	public function add($id, $entityType, $entityId) {

		$collection = $this->retrievePublication($id, Collection::class);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_add)');
		}

		// Delete
		$collectionManager = $this->get(CollectionManager::class);
		$collectionManager->delete($collection);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('collection.collection.form.alert.delete_success', array( '%title%' => $collection->getTitle() )));

		return $this->redirect($this->generateUrl('core_collection_list'));
	}

	/**
	 * @Route("/", name="core_collection_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_collection_list_page")
	 * @Template("Collection/Collection/list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_collection_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
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
			'collection_collection',
			\App\Entity\Collection\Collection::class,
			'core_collection_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'collections' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('Collection/Collection/list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/available/{entityType}/{entityId}", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_collection_list_available")
	 * @Route("/available/{entityType}/{entityId}/{page}", requirements={"entityType" = "\d+", "entityId" = "\d+", "page" = "\d+"}, name="core_collection_list_available_page")
	 * @Template("Collection/Collection/list-available-xhr.html.twig")
	 */
	public function listAvailable($entityType, $entityId, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$collectionRepository = $om->getRepository(Collection::class);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		// Retrieve related entity
		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $collectionRepository->findPaginedByUser($this->getUser(), $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_collection_list_available_page', array( 'entityType' => $entityType, 'entityId' => $entityId ), $page, $paginator->count());

		$collectionnableUtils = $this->get(CollectionnableUtils::class);

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
			return $this->render('Collection/Collection/list-available-n-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{entityType}/{entityId}", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_collection_list_byentity")
	 * @Route("/{entityType}/{entityId}/{page}", requirements={"entityType" = "\d+", "entityId" = "\d+", "page" = "\d+"}, name="core_collection_list_byentity_page")
	 * @Template("Collection/Collection/list-byentity.html.twig")
	 */
	public function listByEntity(Request $request, $entityType, $entityId, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$collectionRepository = $om->getRepository(Collection::class);
		$paginatorUtils = $this->get(PaginatorUtils::class);

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
			return $this->render('Collection/Collection/list-byentity-n-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_collection_show")
	 * @Route("/{id}/tab/{entityType}", requirements={"id" = "\d+", "entityType" = "\d+"}, name="core_collection_show_type")
	 * @Route("/{id}/tab/{entityType}/{page}", requirements={"id" = "\d+", "entityType" = "\d+", "page" = "\d+"}, name="core_collection_show_type_page")
	 * @Template("Collection/Collection/showAbout.html.twig")
	 */
	public function show(Request $request, $id, $entityType = 0, $page = 0) {
		$witnessManager = $this->get(WitnessManager::class);

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
		$dispatcher->dispatch(new PublicationEvent($collection), PublicationListener::PUBLICATION_SHOWN);

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
		$followerUtils = $this->get(FollowerUtils::class);

		$parameters = array(
			'entityType'        => $entityType,
			'collection'        => $collection,
			'permissionContext' => $this->getPermissionContext($collection),
			'likeContext'       => $likableUtils->getLikeContext($collection, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($collection, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($collection),
			'collectionContext' => $collectionnableUtils->getCollectionContext($collection),
			'followerContext'   => $followerUtils->getFollowerContext($collection->getUser(), $this->getUser()),
		);

		if ($entityType > 0) {

			$om = $this->getDoctrine()->getManager();
			$entryRepository = $om->getRepository(Entry::class);
			$paginatorUtils = $this->get(PaginatorUtils::class);
			$typableUtils = $this->get(TypableUtils::class);

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
				return $this->render('Collection/Collection/showEntities-xhr.html.twig', $parameters);
			}
			return $this->render('Collection/Collection/showEntities.html.twig', $parameters);

		}

		return $parameters;
	}

}
