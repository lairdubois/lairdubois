<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Ladb\CoreBundle\Entity\Core\Block\Text;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Qa\Answer;

class ValidAnswerValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof Answer) {
			if ($value->getQuestion()->getUser()->getId() == $value->getUser()->getId()) {

				$body = '';
				foreach ($value->getBodyBlocks() as $block) {
					if ($block instanceof Text) {
						$body .= $block->getBody()."\n";
					}
				}

				if (preg_match('/merci/i', $body)) {
					$this->context->buildViolation('Si vous souhaitez remercier les auteurs des réponses ci-dessus, utilisez les commentaires associés à la question. Merci de bien lire les consignes de rédaction d\'une réponse ci-dessus.')
						->addViolation();
				}
			}
		}
	}

}