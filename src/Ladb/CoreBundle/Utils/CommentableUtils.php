<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Entity\Core\Activity\AbstractActivity;
use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Form\Type\CommentType;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\DraftableInterface;

class CommentableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.commentable_utils';

	/////

	public function deleteComments(CommentableInterface $commentable, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);

		$comments = $commentRepository->findByEntityTypeAndEntityId($commentable->getType(), $commentable->getId());
		foreach ($comments as $comment) {
			if ($commentable instanceof DraftableInterface && !$commentable->getIsDraft()) {
				$comment->getUser()->incrementCommentCount(-1);
			}
			$activityUtils->deleteActivitiesByComment($comment);
			$om->remove($comment);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function incrementUsersCommentCount(CommentableInterface $commentable, $by = 1, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		$comments = $commentRepository->findByEntityTypeAndEntityId($commentable->getType(), $commentable->getId());
		foreach ($comments as $comment) {
			$comment->getUser()->incrementCommentCount($by);
		}
		if ($flush) {
			$om->flush();
		}
	}

	/////

	public function getMentionStrategy(CommentableInterface $commentable) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		$comments = $commentRepository->findByEntityTypeAndEntityId($commentable->getType(), $commentable->getId());
		$mentionStrategy = $this->_getMentionStrategyFromComments($comments);
		if ($commentable instanceof AuthoredInterface) {
			$this->_populateMentionStrategyWithUser($mentionStrategy, $commentable->getUser());
		}
		return json_encode($mentionStrategy);
	}

	private function _getMentionStrategyFromComments($comments) {
		$mentionStrategy = array();
		foreach ($comments as $comment) {
			$user = $comment->getUser();
			$this->_populateMentionStrategyWithUser($mentionStrategy, $user);
		}
		return $mentionStrategy;
	}

	private function _populateMentionStrategyWithUser(&$mentionStrategy, User $user) {
		$imagineCacheManager = $this->get('liip_imagine.cache.manager');
		if (!isset($mentionStrategy[$user->getUsername()])) {
			if (!is_null($user->getAvatar())) {
				$avatar = $imagineCacheManager->getBrowserPath($user->getAvatar()->getWebPath(), '32x32o');
			} else {
				$avatar = $imagineCacheManager->getBrowserPath('avatar.png', '32x32o');
			}
			$mentionStrategy[strtolower($user->getUsername())] = array( 'displayname' => $user->getDisplayName(), 'avatar' => $avatar );
		}
	}

	/////

	public function getCommentContexts($commentables, $includeTimelineActivities = true) {
		$commentContexts = array();
		foreach ($commentables as $commentable) {
			$commentContexts[$commentable->getId()] = $this->getCommentContext($commentable, $includeTimelineActivities);
		}
		return $commentContexts;
	}

	public function getCommentContext(CommentableInterface $commentable, $includeTimelineActivities = true) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);
		$authorizationChecker = $this->get('security.authorization_checker');
		$formFactory = $this->get('form.factory');

		// Retrieve comments
		$comments = $commentRepository->findByEntityTypeAndEntityId($commentable->getType(), $commentable->getId());

		// Retrieve related activities
		$activities = null;
		if ($includeTimelineActivities && $commentable instanceof AbstractPublication) {
			$activityRepository = $om->getRepository(AbstractActivity::CLASS_NAME);
			$activities = $activityRepository->findByPublication($commentable);
		}

		// Define the mentionsStrategy
		if ($authorizationChecker->isGranted('ROLE_USER')) {
			$comment = new Comment();
			$form = $formFactory->createNamed(CommentType::DEFAULT_BLOCK_PREFIX.'_'.$commentable->getType().'_'.$commentable->getId(), CommentType::class, $comment);
			$mentionStrategy = $this->_getMentionStrategyFromComments($comments);
			if ($commentable instanceof AuthoredInterface) {
				$this->_populateMentionStrategyWithUser($mentionStrategy, $commentable->getUser());
			}
		} else {
			$mentionStrategy = null;
		}

		return array(
			'entityType'      => $commentable->getType(),
			'entityId'        => $commentable->getId(),
			'comments'        => $comments,
			'activities'      => $activities,
			'form'            => isset($form) ? $form->createView() : null,
			'isCommentable'   => $commentable instanceof ViewableInterface ? $commentable->getIsViewable() : true,
			'mentionStrategy' => json_encode($mentionStrategy),
		);
	}

	/////

	public function transferComments(CommentableInterface $commentableSrc, CommentableInterface $commentableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		// Retrieve comments
		$comments = $commentRepository->findByEntityTypeAndEntityId($commentableSrc->getType(), $commentableSrc->getId());

		// Transfer comments
		foreach ($comments as $comment) {
			$comment->setEntityType($commentableDest->getType());
			$comment->setEntityId($commentableDest->getId());
		}

		// Update counters
		$commentableDest->incrementCommentCount($commentableSrc->getCommentCount());
		$commentableSrc->setCommentCount(0);

		if ($flush) {
			$om->flush();
		}
	}

}