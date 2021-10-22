<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OneThing extends Constraint {

	public $message = 'Une seule chose';

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}