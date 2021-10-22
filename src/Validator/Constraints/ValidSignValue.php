<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidSignValue extends Constraint {

	public $message = 'Vous devez préciser un nom d\'Agence ou de Boutique.';

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}