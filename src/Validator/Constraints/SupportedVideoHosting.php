<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SupportedVideoHosting extends Constraint {

	public function validatedBy() {
		return 'ladb_core.supported_video_hosting_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}