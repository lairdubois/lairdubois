<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueProvider extends Constraint {

	public $excludedId = 0;
	public $message = 'Ce fournisseur existe déjà.';

	public function validatedBy() {
		return 'ladb_core.unique_provider_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}