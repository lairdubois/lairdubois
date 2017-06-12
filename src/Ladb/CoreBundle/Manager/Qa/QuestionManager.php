<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;

class QuestionManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.question_manager';

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