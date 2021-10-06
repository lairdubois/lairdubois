<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidAnswer extends Constraint {

	public $message = 'Vidéo introuvable.';

	public function validatedBy() {
		return 'ladb_core.valid_answer_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}