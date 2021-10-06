<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ExcludeDomainsLink extends Constraint {

	public $excludedDomainPaterns = array();
	public $message = 'Ce domaine n\'est pas autorisé.';

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}