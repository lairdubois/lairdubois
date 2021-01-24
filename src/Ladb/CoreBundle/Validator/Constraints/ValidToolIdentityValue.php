<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidToolIdentityValue extends Constraint {

	public $message = 'Vous devez préciser le nom du produit.';

	public function validatedBy() {
		return 'ladb_core.valid_tool_identity_value_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}