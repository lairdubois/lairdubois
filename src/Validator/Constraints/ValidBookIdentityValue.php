<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidBookIdentityValue extends Constraint {

	public $message = 'Vous devez préciser le nom du volume.';

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}