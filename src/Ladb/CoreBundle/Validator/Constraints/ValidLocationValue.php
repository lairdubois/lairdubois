<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidLocationValue extends Constraint {

	public function validatedBy() {
		return 'ladb_core.valid_location_value_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}