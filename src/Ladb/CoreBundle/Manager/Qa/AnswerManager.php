<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Qa\Answer;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;

class AnswerManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.qa_answer_manager';

	/////

	public function publish(Answer $answer, $flush = true) {
		parent::publishPublication($answer, $flush);
	}

	public function unpublish(Answer $answer, $flush = true) {
		parent::unpublishPublication($answer, $flush);
	}

	public function delete(Answer $answer, $withWitness = true, $flush = true) {
		parent::deletePublication($answer, $withWitness, $flush);
	}

}