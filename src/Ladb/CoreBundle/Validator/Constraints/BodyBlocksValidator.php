<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Entity\Block\Text;

class BodyBlocksValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof BlockBodiedInterface) {
			$blocks = $value->getBodyBlocks();
			if ($blocks->isEmpty()) {
				$this->context->buildViolation('Cette collection doit contenir au moins un bloc de texte.')
					->atPath('bodyBlocks')
					->addViolation();
			}
			if (!$blocks->isEmpty() && !($blocks->first() instanceof Text)) {
				$this->context->buildViolation('Le premier bloc doit être un bloc de texte.')
					->atPath('bodyBlocks')
					->addViolation();
			}
			$previousBlock = null;
			foreach ($blocks as $block) {
				if (!is_null($previousBlock) && !($previousBlock instanceof Text) && !($block instanceof Text)) {
					$this->context->buildViolation('Un bloc de texte doit obligatoirement être insérer entre les blocs d\'images et vidéo.')
						->atPath('bodyBlocks')
						->addViolation();
				}
				$previousBlock = $block;
			}
		}
	}

}