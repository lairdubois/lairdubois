<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueSoftware extends Constraint {

	public $excludedId = 0;
	public $message = 'Ce logiciel existe déjà.';

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}