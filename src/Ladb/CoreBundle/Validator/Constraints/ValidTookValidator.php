<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Ladb\CoreBundle\Entity\Youtook\Took;
use Ladb\CoreBundle\Utils\VideoHostingUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidTookValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof Took) {
			if ($value->getKind() != VideoHostingUtils::KIND_YOUTUBE) {
				$this->context->buildViolation('Ce lien n\'est pas une URL YouTube valide.')
					->atPath('url')
					->addViolation();
			}
			if (is_null($value->getTitle())) {
				$this->context->buildViolation($constraint->message)
					->atPath('url')
					->addViolation();
			}
		}
	}

}