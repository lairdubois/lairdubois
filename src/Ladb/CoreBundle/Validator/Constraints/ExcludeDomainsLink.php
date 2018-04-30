<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ExcludeDomainsLink extends Constraint {

	public $excludedDomainPaterns = array();
	public $message = 'Ce domaine n\'est pas autorisé.';

	public function validatedBy() {
		return 'ladb_core.exclude_domains_link_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}