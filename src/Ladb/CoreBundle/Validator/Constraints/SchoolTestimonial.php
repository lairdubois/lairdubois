<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SchoolTestimonial extends Constraint {

	public function validatedBy() {
		return 'ladb_core.school_testimonial_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}