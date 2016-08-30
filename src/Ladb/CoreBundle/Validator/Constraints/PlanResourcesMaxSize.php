<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PlanResourcesMaxSize extends Constraint {

	public $maxSize = 10485760;	// 10 Mo
	public $message = 'La taille cumulée des fichiers ne doit pas dépasser 10Mo.';

	public function validatedBy() {
		return 'ladb_core.plan_resources_max_size_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}