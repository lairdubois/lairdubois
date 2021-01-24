<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Knowledge\Value\ToolIdentity;

class ValidToolIdentityValueValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof ToolIdentity) {
			if ($value->getIsProduct() && empty($value->getProductName())) {
				$this->context->buildViolation($constraint->message)
					->atPath('productName')
					->addViolation();
			}
		}
	}

}