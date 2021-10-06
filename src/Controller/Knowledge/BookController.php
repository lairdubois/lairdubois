<?php

namespace App\Controller\Knowledge;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Knowledge\Value\BookIdentity;
use App\Utils\CollectionnableUtils;
use App\Utils\ReviewableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Form\Type\Knowledge\NewBookType;
use App\Form\Model\NewBook;
use App\Entity\Knowledge\Book;
use App\Entity\Knowledge\Value\Text;
use App\Utils\CommentableUtils;
use App\Utils\LikableUtils;
use App\Utils\WatchableUtils;
use App\Utils\SearchUtils;
use App\Utils\ElasticaQueryUtils;
use App\Utils\ActivityUtils;
use App\Utils\KnowledgeUtils;
use App\Manager\Knowledge\BookManager;
use App\Manager\Core\WitnessManager;
use App\Event\PublicationsEvent;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\KnowledgeEvent;
use App\Event\KnowledgeListener;

/**
 * @Route("/livres")
 */
class BookController extends AbstractController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_book_new")
	 * @Template("Knowledge/Book/new.html.twig")
	 */
	public function new() {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_book_new)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::class);

		$newBook = new NewBook();
		$form = $this->createForm(NewBookType::class, $newBook);

		return array(
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_book_create")
	 * @Template("Knowledge/Book/new.html.twig")
	 */
	public function create(Request $request) {

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
			$dispatcher->dispatch(new KnowledgeEvent($book, array( 'field' => Book::FIELD_IDENTITY, 'value' => $identityValue )), KnowledgeListener::FIELD_VALUE_ADDED);
			$dispatcher->dispatch(new KnowledgeEvent($book, array( 'field' => Book::FIELD_COVER, 'value' => $coverValue )), KnowledgeListener::FIELD_VALUE_ADDED);

			$identityValue->setParentEntity($book);
			$identityValue->setParentEntityField(Book::FIELD_IDENTITY);
			$identityValue->setUser($user);

			$coverValue->setParentEntity($book);
			$coverValue->setParentEntityField(Book::FIELD_COVER);
			$coverValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Name and Grain of this new book

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createContributeActivity($identityValue, false);
			$activityUtils->createContributeActivity($coverValue, false);

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($book), PublicationListener::PUBLICATION_CREATED);

			$om->flush();

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($book), PublicationListener::PUBLICATION_PUBLISHED);

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
	public function delete($id) {

		$book = $this->retrievePublication($id, Book::CLASS_NAME);
		$this->assertDeletable($book);

		// Delete
		$bookMananger = $this->get(BookManager::class);
		$bookMananger->delete($book);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.book.form.alert.delete_success', array( '%title%' => $book->getTitle() )));

		return $this->redirect($this->generateUrl('core_book_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_book_widget")
	 * @Template("Knowledge/Book/widget-xhr.html.twig")
	 */
	public function widget($id) {

		$book = $this->retrievePublication($id, Book::CLASS_NAME);
		$this->assertShowable($book, true);

		return array(
			'book' => $book,
		);
	}

	/**
	 * @Route("/", name="core_book_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_book_list_page")
	 * @Template("Knowledge/Book/list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

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

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::class);
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

				//$filters[] = new \Elastica\Query\Range('identityRejected', array( 'lte' => 0 ));
				//$filters[] = new \Elastica\Query\Range('coverRejected', array( 'lt' => 1 ));

			},
			'knowledge_book',
			\App\Entity\Knowledge\Book::CLASS_NAME,
			'core_book_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'books' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('Knowledge/Book/list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_book_show")
	 * @Template("Knowledge/Book/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$bookRepository = $om->getRepository(Book::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

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
		$dispatcher->dispatch(new PublicationEvent($book), PublicationListener::PUBLICATION_SHOWN);

		$searchUtils = $this->get(SearchUtils::class);
		$searchableVolumeCount = $searchUtils->searchEntitiesCount(array( new \Elastica\Query\Match('work', $book->getWork()) ), 'knowledge_book');

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$reviewableUtils = $this->get(ReviewableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);

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
