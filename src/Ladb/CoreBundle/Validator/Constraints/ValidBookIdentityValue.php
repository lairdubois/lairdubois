<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidBookIdentityValue extends Constraint {

	public $message = 'Vous devez préciser le nom du volume.';

	public function validatedBy() {
		return 'ladb_core.valid_book_identity_value_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}