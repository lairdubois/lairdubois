<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Ladb\CoreBundle\Entity\Find\Content\Event;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Core\Block\Text;

class FindBodyBlocksValidator extends BodyBlocksValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		parent::validate($value, $constraint);
		if ($value instanceof Find && !($value->getContent() instanceof Event)) {
			$blocks = $value->getBodyBlocks();
			foreach ($value->getBodyBlocks() as $block) {
				if (!($block instanceof Text)) {
					$this->context->buildViolation('Seules les trouvailles de type Evènement peuvent contenir des images ou vidéos dans leur descriptif.')
						->atPath('bodyBlocks')
						->addViolation();
				}
			}
		}
	}

}