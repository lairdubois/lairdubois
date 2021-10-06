<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PlanResourcesMaxSize extends Constraint {

	public $maxSize = 62914560;	// 60 Mo
	public $message = 'La taille cumulée des fichiers ne doit pas dépasser 60Mo.';

	public function validatedBy() {
		return 'ladb_core.plan_resources_max_size_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}