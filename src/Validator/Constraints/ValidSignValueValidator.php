<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Knowledge\Value\Sign;

class ValidSignValueValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof Sign) {
			if ($value->getIsAffiliate() && empty($value->getStore())) {
				$this->context->buildViolation($constraint->message)
					->atPath('store')
					->addViolation();
			}
		}
	}

}