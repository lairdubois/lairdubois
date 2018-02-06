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
		$currentUser = $this->tokenStorage->getToken()->getUser();
		if (!is_null($currentUser)) {
			if ($value instanceof \Doctrine\Common\Collections\ArrayCollection) {
				foreach ($value as $item) {
					if ($item == $currentUser) {
						$this->context->addViolation($constraint->message);
						break;
					}
				}
			}
			if ($value === $currentUser) {
				$this->context->addViolation($constraint->message);
			}
		}
	}

}