<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Core\Feedback;
use Ladb\CoreBundle\Model\DraftableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Model\FeedbackableInterface;

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

	public function getFeedbackContext(FeedbackableInterface $feedbackable) {
		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);
		$globalUtils = $this->get(GlobalUtils::NAME);

		// Retrieve feedbacks
		$feedbacks = $feedbackRepository->findByEntityTypeAndEntityId($feedbackable->getType(), $feedbackable->getId());

		// Retrieve current user feedback
		$userFeedback = null;
		$user = $globalUtils->getUser();
		if (!is_null($user)) {

			foreach ($feedbacks as $feedback) {
				if ($feedback->getUser() == $user) {
					$userFeedback = $feedback;
					break;
				}
			}

		}

		return array(
			'entityType'     => $feedbackable->getType(),
			'entityId'       => $feedbackable->getId(),
			'feedbackCount'  => $feedbackable->getFeedbackCount(),
			'feedbacks'      => $feedbacks,
			'userFeedback'   => $userFeedback,
			'isFeedbackable' => $feedbackable instanceof HiddableInterface ? $feedbackable->getIsPublic() : true,
		);
	}

}