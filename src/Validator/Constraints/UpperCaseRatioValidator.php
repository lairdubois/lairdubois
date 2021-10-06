<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UpperCaseRatioValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		$charCount = strlen($value);
		if ($charCount > 5) {
			$upperCaseCharCount = preg_match_all('/[A-Z]{1}/', $value);
			$ratio = $upperCaseCharCount / $charCount;
			if ($ratio > $constraint->maxRatio) {
				$this->context->addViolation($constraint->message);
			}
		}
	}

}