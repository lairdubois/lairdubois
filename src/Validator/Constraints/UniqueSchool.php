<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueSchool extends Constraint {

	public $excludedId = 0;
	public $message = 'Cette école existe déjà.';

	public function validatedBy() {
		return 'ladb_core.unique_school_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}