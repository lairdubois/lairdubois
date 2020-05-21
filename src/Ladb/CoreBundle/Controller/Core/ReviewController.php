<?php

namespace Ladb\CoreBundle\Controller\Core;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Model\ReviewableInterface;
use Ladb\CoreBundle\Form\Type\Core\ReviewType;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Entity\Core\Review;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\ReviewableUtils;

/**
 * @Route("/reviews")
 */
class ReviewController extends AbstractController {

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::NAME);
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
	 * @Template("LadbCoreBundle:Core/Review:new-xhr.html.twig")
	 */
	public function newAction(Request $request, $entityType, $entityId) {
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
	 * @Template("LadbCoreBundle:Core/Review:new-xhr.html.twig")
	 */
	public function createAction(Request $request, $entityType, $entityId) {
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

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($review);

			$entity->setChangedAt(new \DateTime());
			$entity->setUpdatedAt(new \DateTime());

			$entity->incrementReviewCount();
			$this->getUser()->getMeta()->incrementReviewCount();

			$om->persist($review);
			$om->flush();	// Need to flush before computing average rating

			// Average rating
			$reviewableUtils = $this->get(ReviewableUtils::NAME);
			$reviewableUtils->computeAverageRating($entity);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createReviewActivity($review, false);

			// Dispatch publication event (on entity)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($entity));

			// Auto watch
			$watchableUtils = $this->container->get(WatchableUtils::NAME);
			$watchableUtils->autoCreateWatch($entity, $this->getUser());

			$om->flush();

			return $this->render('LadbCoreBundle:Core/Review:create-xhr.html.twig', array(
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
	 * @Template("LadbCoreBundle:Core/Review:edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
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
	 * @Template("LadbCoreBundle:Core/Review:edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
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

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($review);

			if ($review->getUser()->getId() == $this->getUser()->getId()) {
				$review->setUpdatedAt(new \DateTime());
			}

			// Average rating
			$reviewableUtils = $this->get(ReviewableUtils::NAME);
			$reviewableUtils->computeAverageRating($entity);

			$om->flush();

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($entity);

			return $this->render('LadbCoreBundle:Core/Review:update-xhr.html.twig', array(
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
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$typableUtils = $this->get(TypableUtils::NAME);

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
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$reviewableUtils->computeAverageRating($entity);

		$om->flush();

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($entity);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('review.'.$typableUtils->getStrippedNameByType($entity->getType()).'.alert.delete_success'));

		return $this->redirect($typableUtils->getUrlAction($entity));
	}

	/**
	 * @Route("/{id}", requirements={"id" = "\d+"}, name="core_review_show")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);
		$typableUtils = $this->get(TypableUtils::NAME);

		$review = $reviewRepository->findOneById($id);
		if (is_null($review)) {
			throw $this->createNotFoundException('Unable to find Review entity (id='.$id.').');
		}

		$entity = $this->_retrieveRelatedEntity($review->getEntityType(), $review->getEntityId());

		return $this->redirect($typableUtils->getUrlAction($entity).'#_review_'.$review->getId());
	}

}
