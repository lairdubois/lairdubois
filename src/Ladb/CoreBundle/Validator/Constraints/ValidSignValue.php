<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidSignValue extends Constraint {

	public $message = 'Vous devez préciser un nom d\'Agence ou de Boutique.';

	public function validatedBy() {
		return 'ladb_core.valid_sign_value_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}