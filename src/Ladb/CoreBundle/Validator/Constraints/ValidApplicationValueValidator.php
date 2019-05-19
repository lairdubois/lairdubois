<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Knowledge\Value\Application;

class ValidApplicationValueValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof Application) {
			if ($value->getIsAddOn() && empty($value->getHostSoftware())) {
				$this->context->buildViolation($constraint->message)
					->atPath('hostSoftware')
					->addViolation();
			}
		}
	}

}