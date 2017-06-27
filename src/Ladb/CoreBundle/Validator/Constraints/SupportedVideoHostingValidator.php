<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Core\Block\Video;
use Ladb\CoreBundle\Utils\VideoHostingUtils;

class SupportedVideoHostingValidator extends ConstraintValidator {

	protected $videoHostingUtils;

	public function __construct(VideoHostingUtils $videoHostingUtils) {
		$this->videoHostingUtils = $videoHostingUtils;
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
		if ($value instanceof Video) {
			$kindAndEmbedIdentifier = $this->videoHostingUtils->getKindAndEmbedIdentifier($value->getUrl());
			if ($kindAndEmbedIdentifier['kind'] == VideoHostingUtils::KIND_UNKNOW) {
				$this->context->buildViolation('Cet hébergeur de vidéos n\'est pas pris en charge.')
					->atPath('url')
					->addViolation();
			}
		}
	}

}