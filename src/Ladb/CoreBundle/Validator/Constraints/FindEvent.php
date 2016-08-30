<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FindEvent extends Constraint {

	public function validatedBy() {
		return 'ladb_core.find_event_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}