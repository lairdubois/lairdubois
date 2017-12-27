<?php

namespace Ladb\CoreBundle\Controller\Core;

use Ladb\CoreBundle\Model\HiddableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Form\Type\CommentType;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\WatchableChildInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\TypableUtils;

/**
 * @Route("/commentaires")
 */
class CommentController extends Controller {

	/**
	 * @Route("/{entityType}/{entityId}/{parentId}/new", requirements={"entityType" = "\d+", "entityId" = "\d+", "parentId" = "\d+"}, name="core_comment_new")
	 * @Template("LadbCoreBundle:Core/Comment:new-xhr.html.twig")
	 */
	public function newAction(Request $request, $entityType, $entityId, $parentId = 0) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

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
			'mentionStrategy' => $mentionStrategy
		);

	}

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

	/**
	 * @Route("/{entityType}/{entityId}/{parentId}/create", requirements={"entityType" = "\d+", "entityId" = "\d+", "parentId" = "\d+"}, name="core_comment_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Core/Comment:new-xhr.html.twig")
	 */
	public function createAction(Request $request, $entityType, $entityId, $parentId = 0) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

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

			// Retrieve parent

			$parentId = intval($parentId);
			if ($parentId > 0) {

				$parent = $commentRepository->findOneByIdAndEntityTypeAndEntityId($parentId, $entityType, $entityId);
				if (!is_null($parent)) {
					$parent->addChild($comment);
					$parent->incrementChildCount();
				}

			}

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessBodyField($comment);

			// Counters

			$entity->incrementCommentCount();
			$this->getUser()->incrementCommentCount();

			$om->persist($comment);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createCommentActivity($comment, false);

			$om->flush();

			// Update index
			if ($entity instanceof IndexableInterface) {
				$searchUtils = $this->get(SearchUtils::NAME);
				$searchUtils->replaceEntityInIndex($entity);
			}

			if ($entity instanceof WatchableInterface) {
				$watchableUtils = $this->get(WatchableUtils::NAME);

				// Auto watch
				$watchableUtils->autoCreateWatch($entity, $this->getUser());

			} else if ($entity instanceof WatchableChildInterface) {
				$watchableUtils = $this->get(WatchableUtils::NAME);

				// Retrive related parent entity

				$typableUtils = $this->get(TypableUtils::NAME);
				try {
					$parentEntity = $typableUtils->findTypable($entity->getParentEntityType(), $entity->getParentEntityId());
				} catch (\Exception $e) {
					throw $this->createNotFoundException($e->getMessage());
				}
				if (!($parentEntity instanceof WatchableInterface)) {
					throw $this->createNotFoundException('Parent Entity must implements WatchableInterface.');
				}

				// Auto watch
				$watchableUtils->autoCreateWatch($parentEntity, $this->getUser());

			}

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
			throw $this->createNotFoundException('Only XML request allowed.');
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
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, name="core_comment_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Core/Comment:edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
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
			$fieldPreprocessorUtils->preprocessBodyField($comment);

			$om->flush();

			return $this->render('LadbCoreBundle:Core/Comment:_row.part.html.twig', array( 'comment' => $comment ));
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
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_comment_delete)');
		}

		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);
		$typableUtils = $this->get(TypableUtils::NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);

		$comment = $commentRepository->findOneById($id);
		if (is_null($comment)) {
			throw $this->createNotFoundException('Unable to find Comment entity (id='.$id.').');
		}

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($comment->getEntityType(), $comment->getEntityId());

		// Delete comment

		$commentableUtils->deleteComment($comment, $entity, $activityUtils, $om, false);

		$om->flush();

		// Update index
		if (isset($entity) && $entity instanceof IndexableInterface) {
			$searchUtils = $this->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($entity);
		}

		return array();
	}

}