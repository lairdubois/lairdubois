<?php

namespace App\Validator\Constraints;

use App\Utils\StringUtils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Core\User;

class ValidDisplaynameValidator extends ConstraintValidator {

    protected ManagerRegistry $om;
    protected StringUtils $stringUtils;

    public function __construct(ManagerRegistry $om, StringUtils $stringUtils) {
        $this->om = $om;
        $this->stringUtils = $stringUtils;
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

			try {
				$displaynameCanonical = $this->stringUtils->canonicalize($value->getDisplayname());
			} catch(\Exception $e) {
				$this->context->buildViolation('Ce nom est invalide.')
					->atPath('displayname')
					->addViolation();
				return;
			}

			if (in_array($displaynameCanonical, ValidUsernameValidator::UNAUTHORIZED_USERNAMES)) {
				$this->context->buildViolation('Ce nom n\'est pas autorisé.')
					->atPath('displayname')
					->addViolation();
			}

			$userRepository = $this->om->getRepository(User::class);
			$currentUser = $value->getId() ? $userRepository->findOneById($value->getId()) : null;
			$user = $userRepository->findOneByDisplaynameCanonical($displaynameCanonical);

			if (!is_null($user) && $user !== $currentUser) {
				$this->context->buildViolation('Ce nom est déjà utilisé.')
					->atPath('displayname')
					->addViolation();
			}

		}
	}

}