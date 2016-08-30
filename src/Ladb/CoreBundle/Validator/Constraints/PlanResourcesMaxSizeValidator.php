<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Wonder\Plan;

class PlanResourcesMaxSizeValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if (!is_null($value) && $value instanceof Plan) {
			$resourcesSize = 0;
			foreach ($value->getResources() as $resource) {
				$resourcesSize += $resource->getFileSize();
			}
			if ($resourcesSize > $constraint->maxSize) {
				$this->context->addViolation($constraint->message);
			}
		}
	}

}