<?php

namespace Ladb\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Comment;
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
	 * @Route("/{entityType}/{entityId}/create", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_comment_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Comment:new-xhr.html.twig")
	 */
	public function createAction(Request $request, $entityType, $entityId) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if ($entity instanceof ViewableInterface && !$entity->getIsViewable()) {
			throw $this->createNotFoundException('Hidden entity could not be commented.');
		}

		$om = $this->getDoctrine()->getManager();

		$comment = new Comment();
		$form = $this->get('form.factory')->createNamed(CommentType::DEFAULT_BLOCK_PREFIX.'_'.$entityType.'_'.$entityId, CommentType::class, $comment);
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Create comment

			$comment->setEntityType($entityType);
			$comment->setEntityId($entityId);
			$comment->setUser($this->getUser());

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

			return $this->render('LadbCoreBundle:Comment:_row.part.html.twig', array( 'comment' => $comment ));
		}

		$commentUtils = $this->get(CommentableUtils::NAME);
		$mentionStrategy = $commentUtils->getMentionStrategy($entity);

		return array(
			'entityType'      => $entityType,
			'entityId'        => $entityId,
			'form'            => $form->createView(),
			'mentionStrategy' => $mentionStrategy
		);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_comment_edit")
	 * @Template("LadbCoreBundle:Comment:edit-xhr.html.twig")
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
	 * @Template("LadbCoreBundle:Comment:edit-xhr.html.twig")
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

			return $this->render('LadbCoreBundle:Comment:_row.part.html.twig', array( 'comment' => $comment ));
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
	 * @Template("LadbCoreBundle:Comment:delete-xhr.html.twig")
	 */
	public function deleteAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_comment_delete)');
		}

		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		$comment = $commentRepository->findOneById($id);
		if (is_null($comment)) {
			throw $this->createNotFoundException('Unable to find Comment entity (id='.$id.').');
		}

		$comment->getUser()->incrementCommentCount(-1);

		// Update related entity

		$typableUtils = $this->get(TypableUtils::NAME);
		try {
			$entity = $typableUtils->findTypable($comment->getEntityType(), $comment->getEntityId());
		} catch (\Exception $e) {
		}
		if ($entity instanceof CommentableInterface) {
			$entity->incrementCommentCount(-1);
		}

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->deleteActivitiesByComment($comment, false);

		$om->remove($comment);
		$om->flush();

		// Update index
		if ($entity instanceof IndexableInterface) {
			$searchUtils = $this->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($entity);
		}

		return array();
	}

}