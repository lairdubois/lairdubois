<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Core\User;

class ValidUserEmailValidator extends ConstraintValidator {

    const UNAUTHORIZED_EMAIL_DOMAINS = array(
        'simplelogin.com',
    );

    protected $container;

	public function __construct(Container $container) {
		$this->container = $container;
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
		if ($value instanceof User) {

		    $emailComponents = explode('@', $value->getEmailCanonical());
		    $emailDomain = end($emailComponents);

			if (in_array($emailDomain, ValidUserEmailValidator::UNAUTHORIZED_EMAIL_DOMAINS)) {
				$this->context->buildViolation('Ce domaine n\'est pas autorisé.')
					->atPath('email')
					->addViolation();
			}

		}
	}

}