<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OneThing extends Constraint {

	public $message = 'Une seule chose';

	public function validatedBy() {
		return 'ladb_core.one_thing_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}