<?php

namespace Ladb\CoreBundle\Manager\Faq;

use Ladb\CoreBundle\Entity\Faq\Question;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;

class QuestionManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.faq_question_manager';

	/////

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