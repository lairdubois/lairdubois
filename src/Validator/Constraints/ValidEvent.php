<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidEvent extends Constraint {

	public function validatedBy() {
		return 'ladb_core.valid_event_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}