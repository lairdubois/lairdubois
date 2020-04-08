<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Knowledge\Value\BookIdentity;

class ValidBookIdentityValueValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof BookIdentity) {
			if ($value->getIsVolume() && empty($value->getVolume())) {
				$this->context->buildViolation($constraint->message)
					->atPath('volume')
					->addViolation();
			}
		}
	}

}