<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValueSource extends Constraint {

	public function validatedBy() {
		return 'ladb_core.value_source_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}