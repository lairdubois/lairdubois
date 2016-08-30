<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class SelfRecipientValidator extends ConstraintValidator {

	protected $tokenStorage;

	public function __construct(TokenStorage $tokenStorage) {
		$this->tokenStorage = $tokenStorage;
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
		if ($value === $this->tokenStorage->getToken()->getUser()) {
			$this->context->addViolation($constraint->message);
		}
	}

}