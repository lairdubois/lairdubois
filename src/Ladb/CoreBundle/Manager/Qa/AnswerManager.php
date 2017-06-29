<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Qa\Answer;
use Ladb\CoreBundle\Manager\AbstractManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\VotableUtils;

class AnswerManager extends AbstractManager {

	const NAME = 'ladb_core.qa_answer_manager';

	/////

	public function delete(Answer $answer, $flush = true) {

		if (!$answer->getQuestion()->getIsDraft()) {

			// Decrement user answer count
			$answer->getUser()->incrementAnswerCount(-1);

		}

		// Delete comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->deleteComments($answer, false);

		// Delete votes
		$votableUtils = $this->get(VotableUtils::NAME);
		$votableUtils->deleteVotes($answer, $answer->getQuestion(), false);

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->deleteActivitiesByAnswer($answer, false);

		parent::deleteEntity($answer, $flush);
	}

}