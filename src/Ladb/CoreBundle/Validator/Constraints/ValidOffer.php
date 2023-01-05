<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidOffer extends Constraint {

	public function validatedBy() {
		return 'ladb_core.valid_offer_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}