<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidTook extends Constraint {

	public $message = 'Vidéo introuvable.';

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}