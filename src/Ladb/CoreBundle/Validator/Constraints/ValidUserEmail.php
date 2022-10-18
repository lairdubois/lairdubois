<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidUserEmail extends Constraint {

	public function validatedBy() {
		return 'ladb_core.valid_user_email_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}