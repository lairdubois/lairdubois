<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidUserEmailValidator extends ConstraintValidator {

    const UNAUTHORIZED_EMAIL_DOMAINS = array(
        'simplelogin.com',
    );

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
    public function validate($value, Constraint $constraint) {

        $emailComponents = explode('@', strtolower($value));
        $emailDomain = end($emailComponents);

        if (in_array($emailDomain, ValidUserEmailValidator::UNAUTHORIZED_EMAIL_DOMAINS)) {
            $this->context->addViolation('Ce domaine n\'est pas autorisé.');
        }
    }

}