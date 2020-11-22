<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;

class ActivityUtils {

	const NAME = 'ladb_core.activity_utils';

	protected $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	/////

	// Create /////

	public function createCommentActivity(\Ladb\CoreBundle\Entity\Core\Comment $comment, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Comment();
		$activity->setUser($comment->getUser());
		$activity->setComment($comment);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createContributeActivity(\Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue $value, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Contribute();
		$activity->setUser($value->getUser());
		$activity->setValue($value);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createFollowActivity(\Ladb\CoreBundle\Entity\Core\Follower $follower, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Follow();
		$activity->setUser($follower->getUser());
		$activity->setFollower($follower);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createLikeActivity(\Ladb\CoreBundle\Entity\Core\Like $like, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Like();
		$activity->setUser($like->getUser());
		$activity->setLike($like);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createMentionActivity(\Ladb\CoreBundle\Entity\Core\Mention $mention, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Mention();
		$activity->setUser($mention->getUser());
		$activity->setMention($mention);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createPublishActivity(\Ladb\CoreBundle\Entity\Core\User $user, $entityType, $entityId, \Ladb\CoreBundle\Entity\Core\User $publisherUser = null, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Publish();
		$activity->setUser($user);
		$activity->setPublisherUser($publisherUser);
		$activity->setEntityType($entityType);
		$activity->setEntityId($entityId);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createVoteActivity(\Ladb\CoreBundle\Entity\Core\Vote $vote, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Vote();
		$activity->setUser($vote->getUser());
		$activity->setVote($vote);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createJoinActivity(\Ladb\CoreBundle\Entity\Core\Join $join, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Join();
		$activity->setUser($join->getUser());
		$activity->setJoin($join);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createWriteActivity(\Ladb\CoreBundle\Entity\Message\Message $message, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Write();
		$activity->setUser($message->getSender());
		$activity->setMessage($message);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createAnswerActivity(\Ladb\CoreBundle\Entity\Qa\Answer $answer, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Answer();
		$activity->setUser($answer->getUser());
		$activity->setAnswer($answer);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createTestifyActivity(\Ladb\CoreBundle\Entity\Knowledge\School\Testimonial $testimonial, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Testify();
		$activity->setUser($testimonial->getUser());
		$activity->setTestimonial($testimonial);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createReviewActivity(\Ladb\CoreBundle\Entity\Core\Review $review, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Review();
		$activity->setUser($review->getUser());
		$activity->setReview($review);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createFeedbackActivity(\Ladb\CoreBundle\Entity\Core\Feedback $feedback, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Feedback();
		$activity->setUser($feedback->getUser());
		$activity->setFeedback($feedback);

		$this->om->persist($activity);

		if ($flush) {
			$this->om->flush();
		}
	}

	public function createInviteActivity(\Ladb\CoreBundle\Entity\Core\MemberInvitation $invitation, $flush = true) {

		$activity = new \Ladb\CoreBundle\Entity\Core\Activity\Invite();
		$activity->setUser($invitation->getSender());
		$activity->setInvitation($invitation);

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

	public function deleteActivitiesByComment(\Ladb\CoreBundle\Entity\Core\Comment $comment, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Comment::CLASS_NAME);
		$activities = $activityRepository->findByComment($comment);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByValue(\Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue $value, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Contribute::CLASS_NAME);
		$activities = $activityRepository->findByValue($value);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByFollower(\Ladb\CoreBundle\Entity\Core\Follower $follower, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Follow::CLASS_NAME);
		$activities = $activityRepository->findByFollower($follower);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByLike(\Ladb\CoreBundle\Entity\Core\Like $like, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Like::CLASS_NAME);
		$activities = $activityRepository->findByLike($like);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByMention(\Ladb\CoreBundle\Entity\Core\Mention $mention, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Mention::CLASS_NAME);
		$activities = $activityRepository->findByMention($mention);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByVote(\Ladb\CoreBundle\Entity\Core\Vote $vote, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Vote::CLASS_NAME);
		$activities = $activityRepository->findByVote($vote);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByJoin(\Ladb\CoreBundle\Entity\Core\Join $join, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Join::CLASS_NAME);
		$activities = $activityRepository->findByJoin($join);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByMessage(\Ladb\CoreBundle\Entity\Message\Message $message, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Write::CLASS_NAME);
		$activities = $activityRepository->findByMessage($message);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByAnswer(\Ladb\CoreBundle\Entity\Qa\Answer $answer, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Answer::CLASS_NAME);
		$activities = $activityRepository->findByAnswer($answer);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByTestimonial(\Ladb\CoreBundle\Entity\Knowledge\School\Testimonial $testimonial, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Testify::CLASS_NAME);
		$activities = $activityRepository->findByTestimonial($testimonial);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByReview(\Ladb\CoreBundle\Entity\Core\Review $review, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Review::CLASS_NAME);
		$activities = $activityRepository->findByReview($review);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByFeedback(\Ladb\CoreBundle\Entity\Core\Feedback $feedback, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Feedback::CLASS_NAME);
		$activities = $activityRepository->findByFeedback($feedback);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByInvitation(\Ladb\CoreBundle\Entity\Core\MemberInvitation $invitation, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Invite::CLASS_NAME);
		$activities = $activityRepository->findByInvitation($invitation);
		$this->_deleteActivities($activities, $flush);
	}

	public function deleteActivitiesByEntityTypeAndEntityId($entityType, $entityId, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Publish::CLASS_NAME);
		$activities = $activityRepository->findByEntityTypeAndEntityId($entityType, $entityId);
		$this->_deleteActivities($activities, $flush);
	}

	// Transfer /////

	public function transferPublishActivities($entityTypeSrc, $entityIdSrc, $entityTypeDest, $entityIdDest, $flush = true) {
		$activityRepository = $this->om->getRepository(\Ladb\CoreBundle\Entity\Core\Activity\Publish::CLASS_NAME);
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