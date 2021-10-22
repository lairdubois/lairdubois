<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SchoolTestimonialValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof \App\Entity\Knowledge\School\Testimonial) {
			if ($value->getToYear() > 0 && $value->getFromYear() > $value->getToYear()) {
				$this->context->buildViolation('L\'année de sortie ne peut pas précéder l\'année d\'entrée.')
					->addViolation();
			}
		}
	}

}