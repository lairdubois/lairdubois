<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidHexColor extends Constraint {

	public $message = 'Cette valeur n\'est pas une couleur valide.';

	public function validatedBy() {
		return 'ladb_core.valid_hex_color_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}