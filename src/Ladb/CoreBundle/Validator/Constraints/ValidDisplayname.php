<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidDisplayname extends Constraint {

	public function validatedBy() {
		return 'ladb_core.valid_displayname_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}