<?php

namespace App\Utils;

use App\Entity\AbstractPublication;
use App\Entity\Core\Activity\AbstractActivity;
use App\Entity\Core\Comment;
use App\Entity\Core\User;
use App\Model\CommentableInterface;
use App\Model\HiddableInterface;
use App\Model\AuthoredInterface;
use App\Model\DraftableInterface;
use App\Model\IndexableInterface;
use App\Model\WatchableChildInterface;
use App\Model\WatchableInterface;

class CommentableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.commentable_utils';

	/////

	public function finalizeNewComment(Comment $comment, CommentableInterface $commentable) {
		$om = $this->getDoctrine()->getManager();

		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
		$fieldPreprocessorUtils->preprocessFields($comment);

		// Counters

		$commentable->incrementCommentCount();
		$comment->getUser()->getMeta()->incrementCommentCount();

		$om->persist($comment);

		// Create activity
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->createCommentActivity($comment, false);

		$om->flush();

		// Process mentions
		$mentionUtils = $this->get(MentionUtils::class);
		$mentionUtils->processMentions($comment);

		// Update index
		if ($commentable instanceof IndexableInterface) {
			$searchUtils = $this->get(SearchUtils::class);
			$searchUtils->replaceEntityInIndex($commentable);
		}

		if ($commentable instanceof WatchableInterface) {
			$watchableUtils = $this->get(WatchableUtils::class);

			// Auto watch
			$watchableUtils->autoCreateWatch($commentable, $comment->getUser());

		} else if ($commentable instanceof WatchableChildInterface) {
			$watchableUtils = $this->get(WatchableUtils::class);

			// Retrive related parent entity

			$typableUtils = $this->get(TypableUtils::class);
			try {
				$parentEntity = $typableUtils->findTypable($commentable->getParentEntityType(), $commentable->getParentEntityId());
			} catch (\Exception $e) {
				throw $this->createNotFoundException($e->getMessage());
			}
			if (!($parentEntity instanceof WatchableInterface)) {
				throw $this->createNotFoundException('Parent Entity must implements WatchableInterface.');
			}

			// Auto watch
			$watchableUtils->autoCreateWatch($parentEntity, $comment->getUser());

		}

	}

	/////

	public function deleteComments(CommentableInterface $commentable, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		$comments = $commentRepository->findByEntityTypeAndEntityId($commentable->getType(), $commentable->getId());
		foreach ($comments as $comment) {
			$this->deleteComment($comment, $commentable, $om, false);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function deleteComment(Comment $comment, CommentableInterface $commentable, $om, $flush = false) {

		// Remove children
		if ($comment->getChildCount() > 0) {
			$children = $comment->getChildren()->toArray();
			$comment->resetChildren();
			foreach ($children as $child) {
				$this->deleteComment($child, $commentable, $om, false);
			}
		}

		// Update user comment count
		if (!($commentable instanceof DraftableInterface) || ($commentable instanceof DraftableInterface && !$commentable->getIsDraft())) {
			$comment->getUser()->getMeta()->incrementCommentCount(-1);
		}

		// Update parent child count
		if (!is_null($comment->getParent())) {
			$comment->getParent()->incrementChildCount(-1);
		}

		// Update commentable comment count
		$commentable->incrementCommentCount(-1);

		// Unlink vote
		if (!is_null($comment->getVote())) {
			$comment->getVote()->setComment(null);
			$comment->setVote(null);
		}

		// Delete mentions
		$mentionUtils = $this->get(MentionUtils::class);
		$mentionUtils->deleteMentions($comment, false);

		// Delete relative activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->deleteActivitiesByComment($comment, false);

		// Remove Comment from DB
		$om->remove($comment);

		if ($flush) {
			$om->flush();
		}

	}

	public function incrementUsersCommentCount(CommentableInterface $commentable, $by = 1, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		$comments = $commentRepository->findByEntityTypeAndEntityId($commentable->getType(), $commentable->getId());
		foreach ($comments as $comment) {
			$comment->getUser()->getMeta()->incrementCommentCount($by);
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

		// Retrieve comments
		$comments = $commentRepository->findByEntityTypeAndEntityId($commentable->getType(), $commentable->getId());

		// Retrieve related activities
		$activities = null;
		if ($includeTimelineActivities && $commentable instanceof AbstractPublication) {
			$activityRepository = $om->getRepository(AbstractActivity::CLASS_NAME);
			$activities = $activityRepository->findByPublication($commentable);
		}

		return array(
			'entityType'      => $commentable->getType(),
			'entityId'        => $commentable->getId(),
			'commentCount'    => $commentable->getCommentCount(),
			'comments'        => $comments,
			'activities'      => $activities,
			'isCommentable'   => $commentable instanceof HiddableInterface ? $commentable->getIsPublic() : true,
		);
	}

	/////

	public function transferComments(CommentableInterface $commentableSrc, CommentableInterface $commentableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);

		// Retrieve comments
		$comments = $commentRepository->findByEntityTypeAndEntityId($commentableSrc->getType(), $commentableSrc->getId(), false);

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

	public function transferChildrenComments(Comment $comment, CommentableInterface $commentableSrc, CommentableInterface $commentableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$children = $comment->getChildren()->toArray();
		$childrenCount = count($children);
		$comment->resetChildren();

		// Transfer comments
		foreach ($children as $comment) {
			$comment->setEntityType($commentableDest->getType());
			$comment->setEntityId($commentableDest->getId());
			$comment->setParent(null);
		}

		// Update counters
		$commentableDest->incrementCommentCount($childrenCount);
		$commentableSrc->incrementCommentCount(-$childrenCount);

		if ($flush) {
			$om->flush();
		}
	}

}