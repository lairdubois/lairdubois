<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidUserDisplayname extends Constraint {

	public function validatedBy() {
		return 'ladb_core.valid_user_displayname_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}