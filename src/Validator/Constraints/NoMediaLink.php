<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NoMediaLink extends Constraint {

	public $message = 'Les liens du type "http://www.lairdubois.fr/media/cache..." ne sont pas permis.';

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}