<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueFind extends Constraint {

	public $message = 'Cette trouvaille existe déjà.';

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}