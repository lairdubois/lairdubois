<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NoMediaLink extends Constraint {

	public $message = 'Les liens du type "http://www.lairdubois.fr/media/cache..." ne sont pas permis.';

	public function validatedBy() {
		return 'ladb_core.no_media_link_validator';
	}

	public function getTargets() {
		return self::PROPERTY_CONSTRAINT;
	}

}