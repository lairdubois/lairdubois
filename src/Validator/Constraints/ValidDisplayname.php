<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidDisplayname extends Constraint {

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}