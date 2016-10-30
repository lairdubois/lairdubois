<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidUsername extends Constraint {

	public function validatedBy() {
		return 'ladb_core.valid_username_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}