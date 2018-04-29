<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue;

class ValueSourceValidator extends ConstraintValidator {

	protected $validator;

	public function __construct(ValidatorInterface $validator) {
		$this->validator = $validator;
	}

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof BaseValue) {
			$sourceType = $value->getSourceType();
			if ($sourceType < BaseValue::SOURCE_TYPE_PERSONAL) {
				$this->context->buildViolation('Vous devez préciser une source')
					->atPath('source')
					->addViolation();
			} else if ($sourceType > BaseValue::SOURCE_TYPE_PERSONAL) {
				$validationGroup = array();
				if ($sourceType == BaseValue::SOURCE_TYPE_WEBSITE) {
					$validationGroup[] = 'website';
				} else if ($sourceType == BaseValue::SOURCE_TYPE_OTHER) {
					$validationGroup[] = 'other';
				}
				$errors = $this->validator->validate($value, null, $validationGroup);
				if (count($errors) > 0) {
					foreach ($errors as $error) {
						$this->context->buildViolation($error->getMessage())
							->atPath('source')
							->addViolation();
					}
				}
			}
		}
	}

}