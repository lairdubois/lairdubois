<?php

namespace Ladb\CoreBundle\Controller\Core;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\MentionUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Model\FeedbackableInterface;
use Ladb\CoreBundle\Form\Type\Core\FeedbackType;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Entity\Core\Feedback;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\FeedbackableUtils;

/**
 * @Route("/feedbacks")
 */
class FeedbackController extends AbstractController {

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::NAME);
		try {
			$entity = $typableUtils->findTypable($entityType, $entityId);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}
		if (!($entity instanceof FeedbackableInterface)) {
			throw $this->createNotFoundException('Entity must implements FeedbackableInterface.');
		}
		return $entity;
	}

	/////

	/**
	 * @Route("/{entityType}/{entityId}/new", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_feedback_new")
	 * @Template("LadbCoreBundle:Core/Feedback:new-xhr.html.twig")
	 */
	public function newAction(Request $request, $entityType, $entityId) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if ($entity instanceof HiddableInterface && $entity->getIsPrivate()) {
			throw $this->createNotFoundException('Hidden entity could not be feedbacked.');
		}

		$feedback = new Feedback();
		$feedback->addBodyBlock(new \Ladb\CoreBundle\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(FeedbackType::class, $feedback);

		return array(
			'entity' => $entity,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/create",requirements={"entityType" = "\d+", "entityId" = "\d+"}, methods={"POST"}, name="core_feedback_create")
	 * @Template("LadbCoreBundle:Core/Feedback:new-xhr.html.twig")
	 */
	public function createAction(Request $request, $entityType, $entityId) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$this->createLock('core_feedback_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if ($entity instanceof HiddableInterface && $entity->getIsPrivate()) {
			throw $this->createNotFoundException('Hidden entity could not be feedbacked.');
		}

		$feedbackableUtils = $this->get(FeedbackableUtils::NAME);
		if (!$feedbackableUtils->getIsFeedbackable($entity, $this->getUser())) {
			throw $this->createNotFoundException('Feedback are not allowed on this entity by this user.');
		}

		$feedback = new Feedback();
		$feedback->setEntityType($entityType);
		$feedback->setEntityId($entityId);
		$feedback->setUser($this->getUser());
		$form = $this->createForm(FeedbackType::class, $feedback);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($feedback);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($feedback);

			$entity->setChangedAt(new \DateTime());
			$entity->setUpdatedAt(new \DateTime());

			$entity->incrementFeedbackCount();
			$this->getUser()->getMeta()->incrementFeedbackCount();

			$om->persist($feedback);
			$om->flush();

			// Process mentions
			$mentionUtils = $this->get(MentionUtils::NAME);
			$mentionUtils->processMentions($feedback);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createFeedbackActivity($feedback, false);

			// Dispatch publication event (on entity)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($entity));

			$om->flush();

			$feedbackableUtils = $this->get(FeedbackableUtils::NAME);

			return $this->render('LadbCoreBundle:Core/Feedback:create-xhr.html.twig', array(
				'entity'          => $entity,
				'feedback'        => $feedback,
				'feedbackContext' => $feedbackableUtils->getFeedbackContext($entity, false),
			));
		}

		return array(
			'entity' => $entity,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_feedback_edit")
	 * @Template("LadbCoreBundle:Core/Feedback:edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);

		$feedback = $feedbackRepository->findOneById($id);
		if (is_null($feedback)) {
			throw $this->createNotFoundException('Unable to find Feedback entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $feedback->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_core_feedback_edit)');
		}

		$form = $this->createForm(FeedbackType::class, $feedback);

		return array(
			'feedback' => $feedback,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_feedback_update")
	 * @Template("LadbCoreBundle:Core/Feedback:edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);

		$feedback = $feedbackRepository->findOneById($id);
		if (is_null($feedback)) {
			throw $this->createNotFoundException('Unable to find Feedback entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $feedback->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_book_feedback_update)');
		}

		$form = $this->createForm(FeedbackType::class, $feedback);
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Retrieve related entity
			$entity = $this->_retrieveRelatedEntity($feedback->getEntityType(), $feedback->getEntityId());

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($feedback);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($feedback);

			if ($feedback->getUser()->getId() == $this->getUser()->getId()) {
				$feedback->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Process mentions
			$mentionUtils = $this->get(MentionUtils::NAME);
			$mentionUtils->processMentions($feedback);

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($entity);

			$feedbackableUtils = $this->get(FeedbackableUtils::NAME);

			return $this->render('LadbCoreBundle:Core/Feedback:update-xhr.html.twig', array(
				'entity'          => $entity,
				'feedback'        => $feedback,
				'feedbackContext' => $feedbackableUtils->getFeedbackContext($entity, false),
			));
		}

		return array(
			'feedback' => $feedback,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_feedback_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);
		$feedbackableUtils = $this->get(FeedbackableUtils::NAME);
		$typableUtils = $this->get(TypableUtils::NAME);

		$feedback = $feedbackRepository->findOneById($id);
		if (is_null($feedback)) {
			throw $this->createNotFoundException('Unable to find Feedback entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $feedback->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_book_feedback_delete)');
		}

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($feedback->getEntityType(), $feedback->getEntityId());

		// Delete comment

		$feedbackableUtils->deleteFeedback($feedback, $entity, $activityUtils, $om, false);

		$om->flush();

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($entity);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('feedback.default.alert.delete_success'));

		return $this->redirect($typableUtils->getUrlAction($entity));
	}

	/**
	 * @Route("/{id}", requirements={"id" = "\d+"}, name="core_feedback_show")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);
		$typableUtils = $this->get(TypableUtils::NAME);

		$feedback = $feedbackRepository->findOneById($id);
		if (is_null($feedback)) {
			throw $this->createNotFoundException('Unable to find Feedback entity (id='.$id.').');
		}

		$entity = $this->_retrieveRelatedEntity($feedback->getEntityType(), $feedback->getEntityId());

		return $this->redirect($typableUtils->getUrlAction($entity).'#_feedback_'.$feedback->getId());
	}

}
