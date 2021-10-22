<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidUsername extends Constraint {

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}