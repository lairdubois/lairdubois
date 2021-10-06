<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use App\Utils\CommentableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Utils\SearchUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\WatchableUtils;
use App\Utils\ActivityUtils;
use App\Utils\TypableUtils;
use App\Model\ReviewableInterface;
use App\Form\Type\Core\ReviewType;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Entity\Core\Review;
use App\Model\HiddableInterface;
use App\Utils\ReviewableUtils;

/**
 * @Route("/reviews")
 */
class ReviewController extends AbstractController {

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::class);
		try {
			$entity = $typableUtils->findTypable($entityType, $entityId);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}
		if (!($entity instanceof ReviewableInterface)) {
			throw $this->createNotFoundException('Entity must implements ReviewableInterface.');
		}
		return $entity;
	}

	/////

	/**
	 * @Route("/{entityType}/{entityId}/new", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_review_new")
	 * @Template("Core/Review/new-xhr.html.twig")
	 */
	public function new(Request $request, $entityType, $entityId) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_review_new)');
		}

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if ($entity instanceof HiddableInterface && $entity->getIsPrivate()) {
			throw $this->createNotFoundException('Hidden entity could not be reviewed.');
		}

		$review = new Review();
		$form = $this->createForm(ReviewType::class, $review);

		return array(
			'entity' => $entity,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/create",requirements={"entityType" = "\d+", "entityId" = "\d+"}, methods={"POST"}, name="core_review_create")
	 * @Template("Core/Review/new-xhr.html.twig")
	 */
	public function create(Request $request, $entityType, $entityId) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_review_create)');
		}

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_knowledge_value_create)');
		}

		$this->createLock('core_review_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if ($entity instanceof HiddableInterface && $entity->getIsPrivate()) {
			throw $this->createNotFoundException('Hidden entity could not be reviewed.');
		}

		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);

		if ($reviewRepository->existsByEntityTypeAndEntityIdAndUser($entityType, $entityId, $this->getUser())) {
			throw $this->createNotFoundException('Only one review is allowed by user.');
		}

		$review = new Review();
		$review->setEntityType($entityType);
		$review->setEntityId($entityId);
		$review->setUser($this->getUser());
		$form = $this->createForm(ReviewType::class, $review);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($review);

			$entity->setChangedAt(new \DateTime());
			$entity->setUpdatedAt(new \DateTime());

			$entity->incrementReviewCount();
			$this->getUser()->getMeta()->incrementReviewCount();

			$om->persist($review);
			$om->flush();	// Need to flush before computing average rating

			// Average rating
			$reviewableUtils = $this->get(ReviewableUtils::class);
			$reviewableUtils->computeAverageRating($entity);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createReviewActivity($review, false);

			// Dispatch publication event (on entity)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($entity), PublicationListener::PUBLICATION_CHANGED);

			// Auto watch
			$watchableUtils = $this->container->get(WatchableUtils::class);
			$watchableUtils->autoCreateWatch($entity, $this->getUser());

			$om->flush();

			return $this->render('Core/Review/create-xhr.html.twig', array(
				'entity' => $entity,
				'review' => $review,
			));
		}

		return array(
			'entity' => $entity,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_review_edit")
	 * @Template("Core/Review/edit-xhr.html.twig")
	 */
	public function edit(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_review_edit)');
		}

		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);

		$review = $reviewRepository->findOneById($id);
		if (is_null($review)) {
			throw $this->createNotFoundException('Unable to find Review entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $review->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_core_review_edit)');
		}

		$form = $this->createForm(ReviewType::class, $review);

		return array(
			'review' => $review,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_review_update")
	 * @Template("Core/Review/edit-xhr.html.twig")
	 */
	public function update(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_review_update)');
		}

		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);

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

			// Retrieve related entity
			$entity = $this->_retrieveRelatedEntity($review->getEntityType(), $review->getEntityId());

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($review);

			if ($review->getUser()->getId() == $this->getUser()->getId()) {
				$review->setUpdatedAt(new \DateTime());
			}

			// Average rating
			$reviewableUtils = $this->get(ReviewableUtils::class);
			$reviewableUtils->computeAverageRating($entity);

			$om->flush();

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::class);
			$searchUtils->replaceEntityInIndex($entity);

			return $this->render('Core/Review/update-xhr.html.twig', array(
				'entity' => $entity,
				'review' => $review,
			));
		}

		return array(
			'review' => $review,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_review_delete")
	 */
	public function delete($id) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::class);
		$reviewableUtils = $this->get(ReviewableUtils::class);
		$typableUtils = $this->get(TypableUtils::class);

		$review = $reviewRepository->findOneById($id);
		if (is_null($review)) {
			throw $this->createNotFoundException('Unable to find Review entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $review->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_book_review_delete)');
		}

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($review->getEntityType(), $review->getEntityId());

		// Delete comment

		$reviewableUtils->deleteReview($review, $entity, $activityUtils, $om, false);

		$om->flush();	// Need to flush before computing average rating

		// Average rating
		$reviewableUtils = $this->get(ReviewableUtils::class);
		$reviewableUtils->computeAverageRating($entity);

		$om->flush();

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->replaceEntityInIndex($entity);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('review.'.$typableUtils->getStrippedNameByType($entity->getType()).'.alert.delete_success'));

		return $this->redirect($typableUtils->getUrlAction($entity));
	}

	/**
	 * @Route("/{id}", requirements={"id" = "\d+"}, name="core_review_show")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);
		$typableUtils = $this->get(TypableUtils::class);

		$review = $reviewRepository->findOneById($id);
		if (is_null($review)) {
			throw $this->createNotFoundException('Unable to find Review entity (id='.$id.').');
		}

		$entity = $this->_retrieveRelatedEntity($review->getEntityType(), $review->getEntityId());

		return $this->redirect($typableUtils->getUrlAction($entity).'#_review_'.$review->getId());
	}

}
