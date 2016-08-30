<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use Ladb\CoreBundle\Entity\Knowledge\Value\Phone;

class ValidPhoneValueValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof Phone) {
			$rawPhoneNumber = $value->getRawPhoneNumber();
			$country = $value->getCountry();

			$phoneUtil = PhoneNumberUtil::getInstance();
			try {

				$phoneNumber = $phoneUtil->parse($rawPhoneNumber, $country);
				if (!$phoneUtil->isValidNumber($phoneNumber)) {
					$this->context->buildViolation('Le numéro de téléphone est invalide pour ce pays')
						->atPath('rawPhoneNumber')
						->addViolation();
				}

			} catch (NumberParseException $e) {
				$this->context->buildViolation('Le numéro de téléphone est invalide')
					->atPath('rawPhoneNumber')
					->addViolation();
			}

		}
	}

}