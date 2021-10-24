<?php

namespace App\Utils;

use Doctrine\ORM\EntityManagerInterface;

class ActivityUtils {

	protected $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	/////

	// Create /////

	public function createCommentActivity(\App\Entity\Core\Comment $comment, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Comment();
		$activity->setUser($comment->getUser());
		$activity->setComment($comment);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createContributeActivity(\App\Entity\Knowledge\Value\BaseValue $value, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Contribute();
		$activity->setUser($value->getUser());
		$activity->setValue($value);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createFollowActivity(\App\Entity\Core\Follower $follower, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Follow();
		$activity->setUser($follower->getUser());
		$activity->setFollower($follower);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createLikeActivity(\App\Entity\Core\Like $like, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Like();
		$activity->setUser($like->getUser());
		$activity->setLike($like);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createMentionActivity(\App\Entity\Core\Mention $mention, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Mention();
		$activity->setUser($mention->getUser());
		$activity->setMention($mention);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createPublishActivity(\App\Entity\Core\User $user, $entityType, $entityId, \App\Entity\Core\User $publisherUser = null, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Publish();
		$activity->setUser($user);
		$activity->setPublisherUser($publisherUser);
		$activity->setEntityType($entityType);
		$activity->setEntityId($entityId);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createVoteActivity(\App\Entity\Core\Vote $vote, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Vote();
		$activity->setUser($vote->getUser());
		$activity->setVote($vote);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createJoinActivity(\App\Entity\Core\Join $join, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Join();
		$activity->setUser($join->getUser());
		$activity->setJoin($join);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createWriteActivity(\App\Entity\Message\Message $message, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Write();
		$activity->setUser($message->getSender());
		$activity->setMessage($message);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createAnswerActivity(\App\Entity\Qa\Answer $answer, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Answer();
		$activity->setUser($answer->getUser());
		$activity->setAnswer($answer);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createTestifyActivity(\App\Entity\Knowledge\School\Testimonial $testimonial, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Testify();
		$activity->setUser($testimonial->getUser());
		$activity->setTestimonial($testimonial);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createReviewActivity(\App\Entity\Core\Review $review, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Review();
		$activity->setUser($review->getUser());
		$activity->setReview($review);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createFeedbackActivity(\App\Entity\Core\Feedback $feedback, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Feedback();
		$activity->setUser($feedback->getUser());
		$activity->setFeedback($feedback);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createInviteActivity(\App\Entity\Core\MemberInvitation $invitation, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Invite();
		$activity->setUser($invitation->getSender());
		$activity->setInvitation($invitation);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createRequestActivity(\App\Entity\Core\MemberRequest $request, $flush = true) {

		$activity = new \App\Entity\Core\Activity\Request();
		$activity->setUser($request->getSender());
		$activity->setRequest($request);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	// Delete /////

	private function _deleteActivities($activities, $flush = true) {
		foreach ($activities as $activity) {
			$this->om->remove($activity);
		}
		if ($flush) {
			$this->om->flush();
		}
	}

	public function deleteActivitiesByComment(\App\Entity\Core\Comment $comment, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Comment::CLASS_NAME);
		$activities = $activityRepository->findByComment($comment);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByValue(\App\Entity\Knowledge\Value\BaseValue $value, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Contribute::CLASS_NAME);
		$activities = $activityRepository->findByValue($value);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByFollower(\App\Entity\Core\Follower $follower, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Follow::CLASS_NAME);
		$activities = $activityRepository->findByFollower($follower);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByLike(\App\Entity\Core\Like $like, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Like::CLASS_NAME);
		$activities = $activityRepository->findByLike($like);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByMention(\App\Entity\Core\Mention $mention, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Mention::CLASS_NAME);
		$activities = $activityRepository->findByMention($mention);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByVote(\App\Entity\Core\Vote $vote, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Vote::CLASS_NAME);
		$activities = $activityRepository->findByVote($vote);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByJoin(\App\Entity\Core\Join $join, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Join::CLASS_NAME);
		$activities = $activityRepository->findByJoin($join);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByMessage(\App\Entity\Message\Message $message, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Write::CLASS_NAME);
		$activities = $activityRepository->findByMessage($message);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByAnswer(\App\Entity\Qa\Answer $answer, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Answer::CLASS_NAME);
		$activities = $activityRepository->findByAnswer($answer);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByTestimonial(\App\Entity\Knowledge\School\Testimonial $testimonial, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Testify::CLASS_NAME);
		$activities = $activityRepository->findByTestimonial($testimonial);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByReview(\App\Entity\Core\Review $review, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Review::CLASS_NAME);
		$activities = $activityRepository->findByReview($review);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByFeedback(\App\Entity\Core\Feedback $feedback, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Feedback::CLASS_NAME);
		$activities = $activityRepository->findByFeedback($feedback);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByInvitation(\App\Entity\Core\MemberInvitation $invitation, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Invite::CLASS_NAME);
		$activities = $activityRepository->findByInvitation($invitation);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByRequest(\App\Entity\Core\MemberRequest $request, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Request::CLASS_NAME);
		$activities = $activityRepository->findByRequest($request);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByEntityTypeAndEntityId($entityType, $entityId, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Publish::CLASS_NAME);
		$activities = $activityRepository->findByEntityTypeAndEntityId($entityType, $entityId);
		$this->_deleteActivities($activities, $flush);
	}

	// Transfer /////

	public function transferPublishActivities($entityTypeSrc, $entityIdSrc, $entityTypeDest, $entityIdDest, $flush = true) {
		$activityRepository = $this->om->getRepository(\App\Entity\Core\Activity\Publish::CLASS_NAME);
		$activities = $activityRepository->findByEntityTypeAndEntityId($entityTypeSrc, $entityIdSrc);

		foreach ($activities as $activity) {
			$activity->setEntityType($entityTypeDest);
			$activity->setEntityId($entityIdDest);
		}

		if ($flush) {
			$this->om->flush();
		}
	}

}