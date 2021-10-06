<?php

namespace App\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Knowledge\Provider;
use App\Entity\Knowledge\Value\Sign;

class UniqueProviderValidator extends ConstraintValidator {

	protected $om;

	public function __construct(ManagerRegistry $om) {
		$this->om = $om;
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
		if (!is_null($value)) {
			if ($value instanceof Sign) {
				$data = $value->getData();
				$providerRepository = $this->om->getRepository(Provider::CLASS_NAME);
				if (!is_null($data) && $providerRepository->existsBySign($data, $constraint->excludedId)) {
					$this->context->buildViolation($constraint->message)
						->atPath('signValue')
						->addViolation();
				}
			}
		}
	}

}