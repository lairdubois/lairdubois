<?php

namespace App\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use App\Fos\DisplaynameCanonicalizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Core\User;

class ValidDisplaynameValidator extends ConstraintValidator {

    protected ManagerRegistry $om;
    protected DisplaynameCanonicalizer $displaynameCanonicalizer;

    public function __construct(ManagerRegistry $om, DisplaynameCanonicalizer $displaynameCanonicalizer) {
        $this->om = $om;
        $this->displaynameCanonicalizer = $displaynameCanonicalizer;
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
				$displaynameCanonical = $this->displaynameCanonicalizer->canonicalize($value->getDisplayname());
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