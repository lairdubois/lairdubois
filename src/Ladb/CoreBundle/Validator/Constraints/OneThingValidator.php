<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class OneThingValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if (preg_match("/(\bou\b)|(\bet\b)|(,)|(;)|(\s-)|(-\s)|(\s-\s)/i", $value)) {
			$this->context->addViolation($constraint->message);
		}
	}

}