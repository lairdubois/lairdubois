<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidSoftwareIdentityValue extends Constraint {

	public $message = 'Vous devez préciser le nom du logiciel hôte.';

	public function validatedBy() {
		return 'ladb_core.valid_software_identity_value_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}