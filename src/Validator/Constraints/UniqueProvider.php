<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueProvider extends Constraint {

	public $excludedId = 0;
	public $message = 'Ce fournisseur existe déjà.';

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}