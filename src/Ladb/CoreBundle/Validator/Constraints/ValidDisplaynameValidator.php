<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Core\UserWitness;
use Ladb\CoreBundle\Fos\DisplaynameCanonicalizer;
use Ladb\CoreBundle\Fos\UserManager;
use Ladb\CoreBundle\Utils\GlobalUtils;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Core\User;

class ValidDisplaynameValidator extends ConstraintValidator {

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

			$displaynameCanonicalizer = $this->container->get(DisplaynameCanonicalizer::NAME);
			try {
				$displaynameCanonical = $displaynameCanonicalizer->canonicalize($value->getDisplayname());
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

			$userRepository = $this->container->get('doctrine')->getRepository(User::class);
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