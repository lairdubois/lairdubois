<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Core\Feedback;
use Ladb\CoreBundle\Entity\Core\Join;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\DraftableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Model\FeedbackableInterface;
use Ladb\CoreBundle\Model\JoinableInterface;

class FeedbackableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.feedbackable_utils';
	
	/////

	public function deleteFeedbacks(FeedbackableInterface $feedbackable, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);

		$feedbacks = $feedbackRepository->findByEntityTypeAndEntityId($feedbackable->getType(), $feedbackable->getId());
		foreach ($feedbacks as $feedback) {
			$this->deleteFeedback($feedback, $feedbackable, $activityUtils, $om, false);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function deleteFeedback(Feedback $feedback, FeedbackableInterface $feedbackable, ActivityUtils $activityUtils, $om, $flush = false) {

		// Update user feedback count
		if (!($feedbackable instanceof DraftableInterface) || ($feedbackable instanceof DraftableInterface && !$feedbackable->getIsDraft())) {
			$feedback->getUser()->getMeta()->incrementFeedbackCount(-1);
		}

		// Update feedbackable feedback count
		$feedbackable->incrementFeedbackCount(-1);

		// Delete relative activities
		$activityUtils->deleteActivitiesByFeedback($feedback);

		// Remove Feedback from DB
		$om->remove($feedback);

		if ($flush) {
			$om->flush();
		}

	}

	/////

	public function getIsFeedbackable(FeedbackableInterface $feedbackable, $user = null) {

		if ($feedbackable instanceof HiddableInterface && !$feedbackable->getIsPublic()) {
			return false;
		}
		if ($feedbackable instanceof AuthoredInterface && $feedbackable->getIsOwner($user)) {
			return true;
		}
		if ($feedbackable instanceof JoinableInterface && !is_null($user)) {
			$om = $this->getDoctrine()->getManager();
			$joinRepository = $om->getRepository(Join::CLASS_NAME);
			return $joinRepository->existsByEntityTypeAndEntityIdAndUser($feedbackable->getType(), $feedbackable->getId(), $user);
		}

		return false;
	}

	/////

	public function getFeedbackContext(FeedbackableInterface $feedbackable, $withFeedbacks = true) {
		$om = $this->getDoctrine()->getManager();

		if ($withFeedbacks) {

			// Retrieve feedbacks
			$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);
			$feedbacks = $feedbackRepository->findByEntityTypeAndEntityId($feedbackable->getType(), $feedbackable->getId());

		}

		$globalUtils = $this->get(GlobalUtils::NAME);

		return array(
			'entityType'     => $feedbackable->getType(),
			'entityId'       => $feedbackable->getId(),
			'feedbackCount'  => $feedbackable->getFeedbackCount(),
			'feedbacks'      => isset($feedbacks) ? $feedbacks : null,
			'isFeedbackable' => $this->getIsFeedbackable($feedbackable, $globalUtils->getUser()),
		);
	}

}