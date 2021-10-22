<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UpperCaseRatio extends Constraint {

	public $message = 'Trop de majuscules.';
	public $maxRatio = 0.35;

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}