<?php

namespace App\Controller\Core;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Controller\AbstractController;
use App\Utils\BlockBodiedUtils;
use App\Utils\MentionUtils;
use App\Utils\SearchUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\ActivityUtils;
use App\Utils\TypableUtils;
use App\Model\FeedbackableInterface;
use App\Form\Type\Core\FeedbackType;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Entity\Core\Feedback;
use App\Model\HiddableInterface;
use App\Utils\FeedbackableUtils;

/**
 * @Route("/feedbacks")
 */
class FeedbackController extends AbstractController {

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::class);
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
	 * @Template("Core/Feedback/new-xhr.html.twig")
	 */
	public function new(Request $request, $entityType, $entityId) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_feedback_new)');
		}

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if ($entity instanceof HiddableInterface && $entity->getIsPrivate()) {
			throw $this->createNotFoundException('Hidden entity could not be feedbacked.');
		}

		$feedback = new Feedback();
		$feedback->addBodyBlock(new \App\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(FeedbackType::class, $feedback);

		return array(
			'entity' => $entity,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/create",requirements={"entityType" = "\d+", "entityId" = "\d+"}, methods={"POST"}, name="core_feedback_create")
	 * @Template("Core/Feedback/new-xhr.html.twig")
	 */
	public function create(Request $request, $entityType, $entityId) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_feedback_create)');
		}

		$this->createLock('core_feedback_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if ($entity instanceof HiddableInterface && $entity->getIsPrivate()) {
			throw $this->createNotFoundException('Hidden entity could not be feedbacked.');
		}

		$feedbackableUtils = $this->get(FeedbackableUtils::class);
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

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($feedback);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($feedback);

			$entity->setChangedAt(new \DateTime());
			$entity->setUpdatedAt(new \DateTime());

			$entity->incrementFeedbackCount();
			$this->getUser()->getMeta()->incrementFeedbackCount();

			$om->persist($feedback);
			$om->flush();

			// Process mentions
			$mentionUtils = $this->get(MentionUtils::class);
			$mentionUtils->processMentions($feedback);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createFeedbackActivity($feedback, false);

			// Dispatch publication event (on entity)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($entity), PublicationListener::PUBLICATION_CHANGED);

			$om->flush();

			$feedbackableUtils = $this->get(FeedbackableUtils::class);

			return $this->render('Core/Feedback/create-xhr.html.twig', array(
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
	 * @Template("Core/Feedback/edit-xhr.html.twig")
	 */
	public function edit(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_feedback_edit)');
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
	 * @Template("Core/Feedback/edit-xhr.html.twig")
	 */
	public function update(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_feedback_update)');
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

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($feedback);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($feedback);

			if ($feedback->getUser()->getId() == $this->getUser()->getId()) {
				$feedback->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Process mentions
			$mentionUtils = $this->get(MentionUtils::class);
			$mentionUtils->processMentions($feedback);

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::class);
			$searchUtils->replaceEntityInIndex($entity);

			$feedbackableUtils = $this->get(FeedbackableUtils::class);

			return $this->render('Core/Feedback/update-xhr.html.twig', array(
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
	public function delete($id) {
		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::class);
		$feedbackableUtils = $this->get(FeedbackableUtils::class);
		$typableUtils = $this->get(TypableUtils::class);

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
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->replaceEntityInIndex($entity);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('feedback.default.alert.delete_success'));

		return $this->redirect($typableUtils->getUrlAction($entity));
	}

	/**
	 * @Route("/{id}", requirements={"id" = "\d+"}, name="core_feedback_show")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);
		$typableUtils = $this->get(TypableUtils::class);

		$feedback = $feedbackRepository->findOneById($id);
		if (is_null($feedback)) {
			throw $this->createNotFoundException('Unable to find Feedback entity (id='.$id.').');
		}

		$entity = $this->_retrieveRelatedEntity($feedback->getEntityType(), $feedback->getEntityId());

		return $this->redirect($typableUtils->getUrlAction($entity).'#_feedback_'.$feedback->getId());
	}

}
