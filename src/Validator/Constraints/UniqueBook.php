<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueBook extends Constraint {

	public $excludedId = 0;
	public $message = 'Ce livre existe déjà.';

	public function validatedBy() {
		return 'ladb_core.unique_book_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}