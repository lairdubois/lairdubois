<?php

namespace App\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Find\Content\Video;
use App\Entity\Find\Content\Website;
use App\Entity\Find\Find;
use App\Entity\Find\Content\Link;

class UniqueFindValidator extends ConstraintValidator {

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
		if (!is_null($value) && $value instanceof Find) {
			$content = $value->getContent();
			if (!is_null($content) && $content instanceof Link) {
				$findRepository = $this->om->getRepository(Find::CLASS_NAME);
				if ($content instanceof Website) {
					if ($findRepository->existsByWebsiteUrl($content->getUrl(), $content->getId())) {
						$this->context->addViolation($constraint->message);
					}
				} else if ($content instanceof Video) {
					if ($findRepository->existsByVideoKindAndEmbedIdentifier($content->getKind(), $content->getEmbedIdentifier(), $content->getId())) {
						$this->context->addViolation($constraint->message);
					}
				}
			}
		}
	}

}