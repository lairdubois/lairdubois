<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueTool extends Constraint {

	public $excludedId = 0;
	public $message = 'Cet outil existe déjà.';

	public function validatedBy() {
		return 'ladb_core.unique_tool_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}