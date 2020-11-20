<?php

namespace Ladb\CoreBundle\Controller\Knowledge;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Controller\PublicationControllerTrait;
use Ladb\CoreBundle\Entity\Knowledge\Value\BookIdentity;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\ReviewableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Form\Type\Knowledge\NewBookType;
use Ladb\CoreBundle\Form\Model\NewBook;
use Ladb\CoreBundle\Entity\Knowledge\Book;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\ElasticaQueryUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\KnowledgeUtils;
use Ladb\CoreBundle\Manager\Knowledge\BookManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\KnowledgeEvent;
use Ladb\CoreBundle\Event\KnowledgeListener;

/**
 * @Route("/livres")
 */
class BookController extends AbstractController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_book_new")
	 * @Template("LadbCoreBundle:Knowledge/Book:new.html.twig")
	 */
	public function newAction() {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_book_new)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::NAME);

		$newBook = new NewBook();
		$form = $this->createForm(NewBookType::class, $newBook);

		return array(
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_book_create")
	 * @Template("LadbCoreBundle:Knowledge/Book:new.html.twig")
	 */
	public function createAction(Request $request) {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_book_create)');
		}

		$this->createLock('core_book_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$dispatcher = $this->get('event_dispatcher');

		$newBook = new NewBook();
		$form = $this->createForm(NewBookType::class, $newBook);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$identityValue = $newBook->getIdentityValue();
			$coverValue = $newBook->getCoverValue();
			$user = $this->getUser();

			// Sanitize Identity values
			if ($identityValue instanceof BookIdentity) {
				$identityValue->setData(trim(ucfirst($identityValue->getData())));
			}

			$book = new Book();
			$book->setTitle($identityValue->getData());
			$book->incrementContributorCount();

			$om->persist($book);
			$om->flush();	// Need to save book to be sure ID is generated

			$book->addIdentityValue($identityValue);
			$book->addCoverValue($coverValue);

			// Dispatch knowledge events
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($book, array( 'field' => Book::FIELD_IDENTITY, 'value' => $identityValue )));
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($book, array( 'field' => Book::FIELD_COVER, 'value' => $coverValue )));

			$identityValue->setParentEntity($book);
			$identityValue->setParentEntityField(Book::FIELD_IDENTITY);
			$identityValue->setUser($user);

			$coverValue->setParentEntity($book);
			$coverValue->setParentEntityField(Book::FIELD_COVER);
			$coverValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Name and Grain of this new book

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createContributeActivity($identityValue, false);
			$activityUtils->createContributeActivity($coverValue, false);

			// Dispatch publication event
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($book));

			$om->flush();

			// Dispatch publication event
			$dispatcher->dispatch(PublicationListener::PUBLICATION_PUBLISHED, new PublicationEvent($book));

			return $this->redirect($this->generateUrl('core_book_show', array('id' => $book->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		return array(
			'newBook'     => $newBook,
			'form'        => $form->createView(),
			'hideWarning' => true,
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_book_delete")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_book_delete)")
	 */
	public function deleteAction($id) {

		$book = $this->retrievePublication($id, Book::CLASS_NAME);
		$this->assertDeletable($book);

		// Delete
		$bookMananger = $this->get(BookManager::NAME);
		$bookMananger->delete($book);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.book.form.alert.delete_success', array( '%title%' => $book->getTitle() )));

		return $this->redirect($this->generateUrl('core_book_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_book_widget")
	 * @Template("LadbCoreBundle:Knowledge/Book:widget-xhr.html.twig")
	 */
	public function widgetAction($id) {

		$book = $this->retrievePublication($id, Book::CLASS_NAME);
		$this->assertShowable($book, true);

		return array(
			'book' => $book,
		);
	}

	/**
	 * @Route("/", name="core_book_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_book_list_page")
	 * @Template("LadbCoreBundle:Knowledge/Book:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_book_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
				switch ($facet->name) {

					// Filters /////

					case 'work':

						$filter = new \Elastica\Query\Match('work', $facet->value);
						$filters[] = $filter;

						break;

					case 'authors':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'authors' ));
						$filters[] = $filter;

						break;

					case 'editor':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'editor' ));
						$filters[] = $filter;

						break;

					case 'collection':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'collection' ));
						$filters[] = $filter;

						break;

					case 'subjects':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'subjects' ));
						$filters[] = $filter;

						break;

					case 'language':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
						$filters[] = $elasticaQueryUtils->createShouldMatchPhraseQuery('language', $facet->value);

						break;

					case 'public-domain':

						$filter = new \Elastica\Query\Range('publicDomain', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'with-review':

						$filter = new \Elastica\Query\Range('reviewCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'rejected':

						$filter = new \Elastica\Query\BoolQuery();
						$filter->addShould(new \Elastica\Query\Range('identityRejected', array( 'gte' => 1 )));
						$filter->addShould(new \Elastica\Query\Range('coverRejected', array( 'gte' => 1 )));
						$filters[] = $filter;

						$noGlobalFilters = true;

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

					case 'sort-popular-rating':
						$sort = array( 'averageRating' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-completion':
						$sort = array( 'completion100' => array( 'order' =>  $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'identity^100', 'authors^50', 'subjects', 'summary', 'toc' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) {

				$filters[] = new \Elastica\Query\Range('identityRejected', array( 'lt' => 1 ));
				$filters[] = new \Elastica\Query\Range('coverRejected', array( 'lt' => 1 ));

			},
			'fos_elastica.index.ladb.knowledge_book',
			\Ladb\CoreBundle\Entity\Knowledge\Book::CLASS_NAME,
			'core_book_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		$parameters = array_merge($searchParameters, array(
			'books' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Knowledge/Book:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_book_show")
	 * @Template("LadbCoreBundle:Knowledge/Book:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$bookRepository = $om->getRepository(Book::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$book = $bookRepository->findOneById($id);
		if (is_null($book)) {
			if ($response = $witnessManager->checkResponse(Book::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Book entity.');
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($book));

		$searchUtils = $this->get(SearchUtils::NAME);
		$searchableVolumeCount = $searchUtils->searchEntitiesCount(array( new \Elastica\Query\Match('work', $book->getWork()) ), 'fos_elastica.index.ladb.knowledge_book');

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);

		return array(
			'book'                  => $book,
			'permissionContext'     => $this->getPermissionContext($book),
			'searchableVolumeCount' => $searchableVolumeCount,
			'likeContext'           => $likableUtils->getLikeContext($book, $this->getUser()),
			'watchContext'          => $watchableUtils->getWatchContext($book, $this->getUser()),
			'commentContext'        => $commentableUtils->getCommentContext($book),
			'collectionContext'     => $collectionnableUtils->getCollectionContext($book),
			'reviewContext'         => $reviewableUtils->getReviewContext($book),
		);
	}

}
