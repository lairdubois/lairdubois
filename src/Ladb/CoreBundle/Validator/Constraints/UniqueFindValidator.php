<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Find\Find;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Find\Content\Link;

class UniqueFindValidator extends ConstraintValidator {

	protected $om;

	public function __construct(ObjectManager $om) {
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
			$content = $value->getContent();
			if (!is_null($content) && $content instanceof Link) {
				$findRepository = $this->om->getRepository(Find::CLASS_NAME);
				if ($findRepository->existsByUrl($content->getUrl(), $content->getId())) {
					$this->context->addViolation($constraint->message);
				}
			}
		}
	}

}