<?php

namespace Ladb\CoreBundle\Controller\Knowledge;

use Ladb\CoreBundle\Manager\Knowledge\BookManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Form\Type\Knowledge\NewBookType;
use Ladb\CoreBundle\Form\Model\NewBook;
use Ladb\CoreBundle\Entity\Knowledge\Book;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\TextureUtils;
use Ladb\CoreBundle\Utils\ElasticaQueryUtils;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\KnowledgeEvent;
use Ladb\CoreBundle\Event\KnowledgeListener;

/**
 * @Route("/livres")
 */
class BookController extends Controller {

	/**
	 * @Route("/new", name="core_book_new")
	 * @Template("LadbCoreBundle:Knowledge/Book:new.html.twig")
	 */
	public function newAction() {

		$newBook = new NewBook();
		$form = $this->createForm(NewBookType::class, $newBook);

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/create", name="core_book_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Knowledge/Book:new.html.twig")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();
		$dispatcher = $this->get('event_dispatcher');

		$newBook = new NewBook();
		$form = $this->createForm(NewBookType::class, $newBook);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$titleValue = $newBook->getTitleValue();
			$coverValue = $newBook->getCoverValue();
			$user = $this->getUser();

			// Sanitize Name values
			if ($titleValue instanceof Text) {
				$titleValue->setData(trim(ucfirst($titleValue->getData())));
			}

			$book = new Book();
			$book->setTitle($titleValue->getData());
			$book->incrementContributorCount();

			$om->persist($book);
			$om->flush();	// Need to save book to be sure ID is generated

			$book->addTitleValue($titleValue);
			$book->addCoverValue($coverValue);

			// Dispatch knowledge events
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($book, array( 'field' => Book::FIELD_TITLE, 'value' => $titleValue )));
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($book, array( 'field' => Book::FIELD_COVER, 'value' => $coverValue )));

			$titleValue->setParentEntity($book);
			$titleValue->setParentEntityField(Book::FIELD_TITLE);
			$titleValue->setUser($user);

			$coverValue->setParentEntity($book);
			$coverValue->setParentEntityField(Book::FIELD_COVER);
			$coverValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Name and Grain of this new book

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createContributeActivity($titleValue, false);
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
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$bookRepository = $om->getRepository(Book::CLASS_NAME);

		$book = $bookRepository->findOneById($id);
		if (is_null($book)) {
			throw $this->createNotFoundException('Unable to find Book entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_book_delete)');
		}

		// Delete
		$bookMananger = $this->get(BookManager::NAME);
		$bookMananger->delete($book);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.book.form.alert.delete_success', array( '%title%' => $book->getTitle() )));

		return $this->redirect($this->generateUrl('core_book_list'));
	}

	/**
	 * @Route("/", name="core_book_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_book_list_page")
	 * @Template("LadbCoreBundle:Knowledge/Book:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				switch ($facet->name) {

					// Filters /////

					case 'title':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
						$filters[] = $elasticaQueryUtils->createShouldMatchQuery('title', $facet->value);

						break;

					case 'author':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
						$filters[] = $elasticaQueryUtils->createShouldMatchQuery('author', $facet->value);

						break;

					case 'editor':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
						$filters[] = $elasticaQueryUtils->createShouldMatchQuery('editor', $facet->value);

						break;

					case 'collection':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
						$filters[] = $elasticaQueryUtils->createShouldMatchQuery('collection', $facet->value);

						break;

					case 'subjects':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
						$filters[] = $elasticaQueryUtils->createShouldMatchQuery('subjects', $facet->value);

						break;

					case 'language':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
						$filters[] = $elasticaQueryUtils->createShouldMatchQuery('language', $facet->value);

						break;

					case 'rejected':

						$filter = new \Elastica\Query\BoolQuery();
						$filter->addShould(new \Elastica\Query\Range('titleRejected', array( 'gte' => 1 )));
						$filter->addShould(new \Elastica\Query\Range('coverRejected', array( 'gte' => 1 )));
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
							$filter->setFields(array( 'title^100', 'summary', 'author' ));
							$filters[] = $filter;

						}

				}
			},
			function(&$filters, &$sort) {

				$filters[] = new \Elastica\Query\Range('titleRejected', array( 'lt' => 1 ));
				$filters[] = new \Elastica\Query\Range('coverRejected', array( 'lt' => 1 ));

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			null,
			'fos_elastica.index.ladb.knowledge_book',
			\Ladb\CoreBundle\Entity\Knowledge\Book::CLASS_NAME,
			'core_book_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities']));

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

		$book = $bookRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($book)) {
			if ($response = $witnessManager->checkResponse(Book::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Book entity.');
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($book));

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);

		return array(
			'book'                    => $book,
			'likeContext'             => $likableUtils->getLikeContext($book, $this->getUser()),
			'watchContext'            => $watchableUtils->getWatchContext($book, $this->getUser()),
			'commentContext'          => $commentableUtils->getCommentContext($book),
		);
	}

}
