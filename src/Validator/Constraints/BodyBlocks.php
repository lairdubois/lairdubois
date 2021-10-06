<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class BodyBlocks extends Constraint {

	public function validatedBy() {
		return 'ladb_core.body_blocks_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}