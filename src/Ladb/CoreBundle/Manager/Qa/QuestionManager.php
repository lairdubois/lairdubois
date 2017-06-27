<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Manager\WitnessManager;

class QuestionManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.qa_question_manager';

	/////

	public function publish(Question $question, $flush = true) {
		parent::publishPublication($question, $flush);
	}

	public function unpublish(Question $question, $flush = true) {
		parent::unpublishPublication($question, $flush);
	}

	public function delete(Question $question, $withWitness = true, $flush = true) {

		// Delete answers
		$answerManager = $this->get(AnswerManager::NAME);
		foreach ($question->getAnswer() as $answer) {

			// Delete Answer
			$answerManager->delete($answer, $withWitness, $flush);

		}

		parent::deletePublication($question, $withWitness, $flush);
	}

}