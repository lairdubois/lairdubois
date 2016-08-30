<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Knowledge\Value\Location;

class ValidLocationValueValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof Location) {
			if (!empty($value->getLocation()) && is_null($value->getLatitude()) && is_null($value->getLongitude())) {
				$this->context->buildViolation('Cette adresse est inconnue ou incorrecte.')
					->atPath('location')
					->addViolation();
			}
		}
	}

}