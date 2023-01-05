<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Ladb\CoreBundle\Entity\Offer\Offer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Core\Block\Text;

class ValidOfferValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof Offer && $value->getKind() == Offer::KIND_OFFER && $value->getCategory() != Offer::CATEGORY_JOB) {
            $blocks = $value->getBodyBlocks();
            foreach ($blocks as $block) {
                if ($block instanceof Text && preg_match('/faire\s*(?:une|1)*\s*off*re/mi', $block->getBody())) {
                    $this->context->buildViolation('Vous devez indiquez un prix fixe représentant l\'intégralité de ce que présente l\'annonce.')
                        ->atPath('bodyBlocks')
                        ->addViolation();
                    break;
                }
            }
		}
	}

}