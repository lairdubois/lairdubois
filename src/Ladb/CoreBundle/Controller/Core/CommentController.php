<?php

namespace Ladb\CoreBundle\Controller\Core;

use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Manager\Core\CommentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue;
use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Form\Type\Core\CommentType;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\WatchableChildInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Utils\MentionUtils;

/**
 * @Route("/commentaires")
 */
class CommentController extends AbstractController {

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::NAME);
		try {
			$entity = $typableUtils->findTypable($entityType, $entityId);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}
		if (!($entity instanceof CommentableInterface)) {
			throw $this->createNotFoundException('Entity must implements CommentableInterface.');
		}
		return $entity;
	}

	/////

	/**
	 * @Route("/{entityType}/{entityId}/{parentId}/new", requirements={"entityType" = "\d+", "entityId" = "\d+", "parentId" = "\d+"}, name="core_comment_new")
	 * @Template("LadbCoreBundle:Core/Comment:new-xhr.html.twig")
	 */
	public function newAction(Request $request, $entityType, $entityId, $parentId = 0) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_comment_new)');
		}

		$alertTransKey = $request->get('alertTransKey', '');

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if ($entity instanceof HiddableInterface && $entity->getIsPrivate()) {
			throw $this->createNotFoundException('Hidden entity could not be commented.');
		}

		$comment = new Comment();
		$form = $this->get('form.factory')->createNamed(CommentType::DEFAULT_BLOCK_PREFIX.'_'.$entityType.'_'.$entityId.'_'.$parentId, CommentType::class, $comment);

		$commentUtils = $this->get(CommentableUtils::NAME);
		$mentionStrategy = $commentUtils->getMentionStrategy($entity);

		return array(
			'entityType'      => $entityType,
			'entityId'        => $entityId,
			'parentId'        => $parentId,
			'form'            => $form->createView(),
			'mentionStrategy' => $mentionStrategy,
			'alertTransKey'   => $alertTransKey
		);

	}

	/**
	 * @Route("/{entityType}/{entityId}/{parentId}/create", requirements={"entityType" = "\d+", "entityId" = "\d+", "parentId" = "\d+"}, methods={"POST"}, name="core_comment_create")
	 * @Template("LadbCoreBundle:Core/Comment:new-xhr.html.twig")
	 */
	public function createAction(Request $request, $entityType, $entityId, $parentId = 0) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_comment_create)');
		}

		$this->createLock('core_comment_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if ($entity instanceof HiddableInterface && $entity->getIsPrivate()) {
			throw $this->createNotFoundException('Hidden entity could not be commented.');
		}

		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		$comment = new Comment();
		$form = $this->get('form.factory')->createNamed(CommentType::DEFAULT_BLOCK_PREFIX.'_'.$entityType.'_'.$entityId.'_'.$parentId, CommentType::class, $comment);
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Create comment

			$comment->setEntityType($entityType);
			$comment->setEntityId($entityId);
			$comment->setUser($this->getUser());

            // Add some LOVE

            if ($entityType == Find::TYPE && $entityId == 2949) {
                $comment->setBody('😘');
            }

			// Retrieve parent

			$parentId = intval($parentId);
			if ($parentId > 0) {

				$parent = $commentRepository->findOneByIdAndEntityTypeAndEntityId($parentId, $entityType, $entityId);
				if (!is_null($parent)) {
					$parent->addChild($comment);
					$parent->incrementChildCount();
				}

			}

			$commentableUtils = $this->get(CommentableUtils::NAME);
			$commentableUtils->finalizeNewComment($comment, $entity);

			return $this->render('LadbCoreBundle:Core/Comment:create-xhr.html.twig', array( 'comment' => $comment ));
		}

		$commentUtils = $this->get(CommentableUtils::NAME);
		$mentionStrategy = $commentUtils->getMentionStrategy($entity);

		return array(
			'entityType'      => $entityType,
			'entityId'        => $entityId,
			'parentId'        => $parentId,
			'form'            => $form->createView(),
			'mentionStrategy' => $mentionStrategy
		);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_comment_edit")
	 * @Template("LadbCoreBundle:Core/Comment:edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_comment_edit)');
		}

		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		$comment = $commentRepository->findOneById($id);
		if (is_null($comment)) {
			throw $this->createNotFoundException('Unable to find Comment entity (id='.$id.').');
		}

		$form = $this->get('form.factory')->createNamed(CommentType::DEFAULT_BLOCK_PREFIX.'_'.$comment->getEntityType().'_'.$comment->getEntityId(), CommentType::class, $comment);

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($comment->getEntityType(), $comment->getEntityId());

		$commentUtils = $this->get(CommentableUtils::NAME);
		$mentionStrategy = $commentUtils->getMentionStrategy($entity);

		return array(
			'comment'         => $comment,
			'form'            => $form->createView(),
			'mentionStrategy' => $mentionStrategy
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_comment_update")
	 * @Template("LadbCoreBundle:Core/Comment:edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_comment_update)');
		}

		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		$comment = $commentRepository->findOneById($id);
		if (is_null($comment)) {
			throw $this->createNotFoundException('Unable to find Comment entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $comment->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_comment_update)');
		}

		$picturedUtils = $this->get(PicturedUtils::NAME);
		$picturedUtils->resetPictures($comment); // Reset pictures array to consider form pictures order

		$form = $this->get('form.factory')->createNamed(CommentType::DEFAULT_BLOCK_PREFIX.'_'.$comment->getEntityType().'_'.$comment->getEntityId(), CommentType::class, $comment);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($comment);

			$om->flush();

			// Process mentions
			$mentionUtils = $this->get(MentionUtils::NAME);
			$mentionUtils->processMentions($comment);

			return $this->render('LadbCoreBundle:Core/Comment:update-xhr.html.twig', array( 'comment' => $comment ));
		}

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($comment->getEntityType(), $comment->getEntityId());

		$commentUtils = $this->get(CommentableUtils::NAME);
		$mentionStrategy = $commentUtils->getMentionStrategy($entity);

		return array(
			'comment'         => $comment,
			'form'            => $form->createView(),
			'mentionStrategy' => $mentionStrategy
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_comment_delete")
	 * @Template("LadbCoreBundle:Core/Comment:delete-xhr.html.twig")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);

		$comment = $commentRepository->findOneById($id);
		if (is_null($comment)) {
			throw $this->createNotFoundException('Unable to find Comment entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && ($comment->getUser()->getId() != $this->getUser()->getId() || $comment->getChildCount() > 0)) {
			throw $this->createNotFoundException('Not allowed (core_comment_delete)');
		}

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($comment->getEntityType(), $comment->getEntityId());

		// Delete comment

		$commentableUtils->deleteComment($comment, $entity, $om, false);

		$om->flush();

		// Update index
		if (isset($entity) && $entity instanceof IndexableInterface) {
			$searchUtils = $this->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($entity);
		}

		return array();
	}

	/**
	 * @Route("/{id}", requirements={"id" = "\d+"}, name="core_comment_show")
	 */
	public function showAction($id) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);
		$typableUtils = $this->get(TypableUtils::NAME);

		$comment = $commentRepository->findOneById($id);
		if (is_null($comment)) {
			throw $this->createNotFoundException('Unable to find Comment entity (id='.$id.').');
		}

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($comment->getEntityType(), $comment->getEntityId());
		if ($entity instanceof WatchableChildInterface && !($entity instanceof BaseValue)) {
			$entity = $typableUtils->findTypable($entity->getParentEntityType(), $entity->getParentEntityId());
		}

		return $this->redirect($typableUtils->getUrlAction($entity).'#_comment_'.$comment->getId());
	}

	/**
	 * @Route("/{id}/admin/moveup", requirements={"id" = "\d+"}, name="core_comment_admin_moveup")
	 * @Template("LadbCoreBundle:Core/Comment:moveup-xhr.html.twig")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_comment_admin_moveup)")
	 */
	public function adminMoveupAction($id) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		$comment = $commentRepository->findOneById($id);
		if (is_null($comment)) {
			throw $this->createNotFoundException('Unable to find Comment entity (id='.$id.').');
		}

		// Moveup comment

		$parent = $comment->getParent();
		if ($parent) {
			$parent->removeChild($comment);
			$parent->incrementChildCount(-1);
		}

		$om->flush();

		return array();
	}

	/**
	 * @Route("/{id}/{questionId}/admin/converttoanswer", requirements={"id" = "\d+", "questionId" = "\d+"}, name="core_comment_admin_converttoanswer")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_comment_admin_converttoanswer)")
	 */
	public function adminConvertToAnswerAction($id, $questionId) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);
		$questionRepository = $om->getRepository(Question::CLASS_NAME);

		$comment = $commentRepository->findOneById($id);
		if (is_null($comment)) {
			throw $this->createNotFoundException('Unable to find Comment entity (id='.$id.').');
		}
		$question = $questionRepository->findOneById($questionId);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}

		// Convert
		$commentManager = $this->get(CommentManager::NAME);
		$answer = $commentManager->convertToAnswer($comment, $question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('comment.admin.alert.converttoanswer_success'));

		return $this->redirect($this->generateUrl('core_qa_answer_show', array( 'id' => $answer->getId() )));
	}


}