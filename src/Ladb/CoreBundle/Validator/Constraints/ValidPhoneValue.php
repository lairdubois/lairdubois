<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidPhoneValue extends Constraint {

	public function validatedBy() {
		return 'ladb_core.valid_phone_value_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}