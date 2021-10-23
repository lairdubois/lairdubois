<?php

namespace App\Manager\Faq;

use App\Entity\Faq\Question;
use App\Manager\AbstractPublicationManager;

class QuestionManager extends AbstractPublicationManager {

	public function publish(Question $question, $flush = true) {
		parent::publishPublication($question, $flush);
	}

	public function unpublish(Question $question, $flush = true) {
		parent::unpublishPublication($question, $flush);
	}

	public function delete(Question $question, $withWitness = true, $flush = true) {
		parent::deletePublication($question, $withWitness, $flush);
	}

}