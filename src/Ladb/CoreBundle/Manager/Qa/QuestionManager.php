<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Manager\WitnessManager;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\VotableUtils;

class QuestionManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.qa_question_manager';

	/////

	public function publish(Question $question, $flush = true) {

		$question->getUser()->incrementDraftQuestionCount(-1);
		$question->getUser()->incrementPublishedQuestionCount();

		foreach ($question->getAnswers() as $answer) {

			// Increment user answer count
			$answer->getUser()->incrementAnswerCount(1);

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

		$question->getUser()->incrementDraftQuestionCount(1);
		$question->getUser()->incrementPublishedQuestionCount(-1);

		foreach ($question->getAnswers() as $answer) {

			// Decrement user answer count
			$answer->getUser()->incrementAnswerCount(-1);

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
			$question->getUser()->incrementDraftQuestionCount(-1);
		} else {
			$question->getUser()->incrementPublishedQuestionCount(-1);
		}

		$answerManager = $this->get(AnswerManager::NAME);
		foreach ($question->getAnswers() as $answer) {

			// Delete answer
			$answerManager->delete($answer, false);

		}

		parent::deletePublication($question, $withWitness, $flush);
	}

}