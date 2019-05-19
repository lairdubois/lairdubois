<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueSoftware extends Constraint {

	public $excludedId = 0;
	public $message = 'Ce logiciel existe déjà.';

	public function validatedBy() {
		return 'ladb_core.unique_software_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}