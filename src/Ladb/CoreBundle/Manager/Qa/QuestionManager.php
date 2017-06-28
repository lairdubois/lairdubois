<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Manager\WitnessManager;

class QuestionManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.qa_question_manager';

	/////

	public function publish(Question $question, $flush = true) {

		$question->getUser()->incrementDraftQuestionCount(-1);
		$question->getUser()->incrementPublishedQuestionCount();

		parent::publishPublication($question, $flush);
	}

	public function unpublish(Question $question, $flush = true) {

		$question->getUser()->incrementDraftQuestionCount(1);
		$question->getUser()->incrementPublishedQuestionCount(-1);

		parent::unpublishPublication($question, $flush);
	}

	public function delete(Question $question, $withWitness = true, $flush = true) {

		// Decrement user creation count
		if ($question->getIsDraft()) {
			$question->getUser()->incrementDraftQuestionCount(-1);
		} else {
			$question->getUser()->incrementPublishedQuestionCount(-1);
		}

		parent::deletePublication($question, $withWitness, $flush);
	}

}