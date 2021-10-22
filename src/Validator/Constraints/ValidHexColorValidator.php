<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Knowledge\Value\Location;

class ValidHexColorValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if (!preg_match('/^#[a-f0-9]{6}$/i', $value)) {
			$this->context->addViolation($constraint->message);
		}
	}

}