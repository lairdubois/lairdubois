<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidHexColor extends Constraint {

	public $message = 'Cette valeur n\'est pas une couleur valide.';

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}