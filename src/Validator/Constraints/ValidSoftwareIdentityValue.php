<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidSoftwareIdentityValue extends Constraint {

	public $message = 'Vous devez préciser le nom du logiciel hôte.';

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}