<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidEventValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof \Ladb\CoreBundle\Entity\Event\Event) {
			if ($value->getStartAt() > $value->getEndAt()) {
				$this->context->buildViolation('La fin de l\'évènement ne peut pas précéder son début.')
					->addViolation();
			}
		}
	}

}