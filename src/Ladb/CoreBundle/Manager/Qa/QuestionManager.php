<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Manager\WitnessManager;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\VotableUtils;

class QuestionManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.qa_question_manager';

	/////

	public function publish(Question $question, $flush = true) {

		$question->getUser()->getMeta()->incrementPrivateQuestionCount(-1);
		$question->getUser()->getMeta()->incrementPublicQuestionCount();

		foreach ($question->getAnswers() as $answer) {

			// Increment user answer count
			$answer->getUser()->getMeta()->incrementAnswerCount(1);

			// Increment users comment counters
			$commentableUtils = $this->container->get(CommentableUtils::NAME);
			$commentableUtils->incrementUsersCommentCount($answer, 1);

			// Increment users vote counters
			$votableUtils = $this->container->get(VotableUtils::NAME);
			$votableUtils->incrementUsersVoteCount($answer, 1);

		}

		parent::publishPublication($question, $flush);
	}

	public function unpublish(Question $question, $flush = true) {

		$question->getUser()->getMeta()->incrementPrivateQuestionCount(1);
		$question->getUser()->getMeta()->incrementPublicQuestionCount(-1);

		foreach ($question->getAnswers() as $answer) {

			// Decrement user answer count
			$answer->getUser()->getMeta()->incrementAnswerCount(-1);

			// Decrement users comment counters
			$commentableUtils = $this->container->get(CommentableUtils::NAME);
			$commentableUtils->incrementUsersCommentCount($answer, -1);

			// Decrement users vote counters
			$votableUtils = $this->container->get(VotableUtils::NAME);
			$votableUtils->incrementUsersVoteCount($answer, -1);

		}

		parent::unpublishPublication($question, $flush);
	}

	public function delete(Question $question, $withWitness = true, $flush = true) {

		// Decrement user creation count
		if ($question->getIsDraft()) {
			$question->getUser()->getMeta()->incrementPrivateQuestionCount(-1);
		} else {
			$question->getUser()->getMeta()->incrementPublicQuestionCount(-1);
		}

		$answerManager = $this->get(AnswerManager::NAME);
		foreach ($question->getAnswers() as $answer) {

			// Delete answer
			$answerManager->delete($answer, false);

		}

		parent::deletePublication($question, $withWitness, $flush);
	}

	public function computeAnswerCounters(Question $question) {

		$positiveAnswerCount = 0;
		$nullAnswerCount = 0;
		$undeterminedAnswerCount = 0;
		$negativeAnswerCount = 0;

		foreach ($question->getAnswers() as $answer) {
			if ($answer->getVoteScore() > 0) {
				$positiveAnswerCount++;
			} else if ($answer->getVoteScore() < 0) {
				$negativeAnswerCount++;
			} else if ($answer->getVoteScore() == 0 && $answer->getPositiveVoteScore() > 0) {
				$undeterminedAnswerCount++;
			} else {
				$nullAnswerCount++;
			}
		}

		$question->setPositiveAnswerCount($positiveAnswerCount);
		$question->setNullAnswerCount($nullAnswerCount);
		$question->setUndeterminedAnswerCount($undeterminedAnswerCount);
		$question->setNegativeAnswerCount($negativeAnswerCount);

	}

}