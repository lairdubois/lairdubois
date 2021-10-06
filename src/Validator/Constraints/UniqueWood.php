<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueWood extends Constraint {

	public $excludedId = 0;
	public $message = 'Cette essence existe déjà.';

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}