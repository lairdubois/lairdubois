<?php

namespace Ladb\CoreBundle\Controller\Knowledge\Book;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\BookUtils;
use Ladb\CoreBundle\Entity\Knowledge\Book;
use Ladb\CoreBundle\Form\Type\Knowledge\Book\ReviewType;
use Ladb\CoreBundle\Manager\Knowledge\Book\ReviewManager;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;

/**
 * @Route("/livres")
 */
class ReviewController extends Controller {

	/**
	 * @Route("/{id}/critiques/new", requirements={"id" = "\d+"}, name="core_knowledge_book_review_new")
	 * @Template("LadbCoreBundle:Knowledge/Book/Review:new-xhr.html.twig")
	 */
	public function newAction($id) {
		$om = $this->getDoctrine()->getManager();
		$bookRepository = $om->getRepository(Book::CLASS_NAME);

		$book = $bookRepository->findOneById($id);
		if (is_null($book)) {
			throw $this->createNotFoundException('Unable to find Book entity (id='.$id.').');
		}

		$review = new Book\Review();
		$form = $this->createForm(ReviewType::class, $review);

		return array(
			'book' => $book,
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/critiques/create", requirements={"id" = "\d+"}, name="core_knowledge_book_review_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Knowledge/Book/Review:new-xhr.html.twig")
	 */
	public function createAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$bookRepository = $om->getRepository(Book::CLASS_NAME);

		$book = $bookRepository->findOneById($id);
		if (is_null($book)) {
			throw $this->createNotFoundException('Unable to find Book entity (id='.$id.').');
		}

		$review = new Book\Review();
		$review->setBook($book);				// Used by validator
		$review->setUser($this->getUser());		// Used by validator
		$form = $this->createForm(ReviewType::class, $review);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($review);

			$review->setUser($this->getUser());

			$book->addReview($review);

			$book->incrementReviewCount();
			$this->getUser()->getMeta()->incrementReviewCount();

			// Average rating
			$bookUtils = $this->get(BookUtils::NAME);
			$bookUtils->computeAverageRating($book);

			$om->persist($review);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createReviewActivity($review, false);

			// Dispatch publication event (on Book)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($book));

			// Auto watch
			$watchableUtils = $this->container->get(WatchableUtils::NAME);
			$watchableUtils->autoCreateWatch($book, $this->getUser());

			$om->flush();

			return $this->render('LadbCoreBundle:Knowledge/Book/Review:create-xhr.html.twig', array(
				'book'   => $book,
				'review' => $review,
			));
		}

		return array(
			'book' => $book,
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/critiques/{id}/edit", requirements={"id" = "\d+"}, name="core_knowledge_book_review_edit")
	 * @Template("LadbCoreBundle:Knowledge/Book/Review:edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Book\Review::CLASS_NAME);

		$review = $reviewRepository->findOneById($id);
		if (is_null($review)) {
			throw $this->createNotFoundException('Unable to find Review entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $review->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_book_review_edit)');
		}

		$form = $this->createForm(ReviewType::class, $review);

		return array(
			'review' => $review,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/critiques/{id}/update", requirements={"id" = "\d+"}, name="core_knowledge_book_review_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Knowledge/Book/Review:edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Book\Review::CLASS_NAME);

		$review = $reviewRepository->findOneById($id);
		if (is_null($review)) {
			throw $this->createNotFoundException('Unable to find Review entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $review->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_book_review_update)');
		}

		$form = $this->createForm(ReviewType::class, $review);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($review);

			if ($review->getUser()->getId() == $this->getUser()->getId()) {
				$review->setUpdatedAt(new \DateTime());
			}

			// Average rating
			$bookUtils = $this->get(BookUtils::NAME);
			$bookUtils->computeAverageRating($review->getBook());

			$om->flush();

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($review->getBook());

			return $this->render('LadbCoreBundle:Knowledge/Book/Review:_row.part.html.twig', array(
				'book'   => $review->getBook(),
				'review' => $review,
			));
		}

		return array(
			'review' => $review,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/critiques/{id}/delete", requirements={"id" = "\d+"}, name="core_knowledge_book_review_delete")
	 */
	public function deleteAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Book\Review::CLASS_NAME);

		$review = $reviewRepository->findOneById($id);
		if (is_null($review)) {
			throw $this->createNotFoundException('Unable to find Review entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $review->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_book_review_delete)');
		}

		$book = $review->getBook();

		// Delete
		$reviewManager = $this->get(ReviewManager::NAME);
		$reviewManager->delete($review);

		// Average rating
		$bookUtils = $this->get(BookUtils::NAME);
		$bookUtils->computeAverageRating($book);

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($book);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.book.review.form.alert.delete_success'));

		return $this->redirect($this->generateUrl('core_book_show', array( 'id' => $book->getSluggedId() )));
	}

}
