<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Knowledge\Value\Url;

class ExcludeDomainsLinkValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if (is_string($value)) {
			$url = $value;
		} elseif ($value instanceof Url) {
			$url = $value->getData();
		} else {
			$url = null;
		}
		if (!is_null($url) && !empty($url)) {
			$components = parse_url($url);
			if (isset($components['host'])) {
				$host = $components['host'];
				foreach ($constraint->excludedDomainPaterns as $excludedDomainPatern) {
					if (preg_match($excludedDomainPatern, $host)) {
						$this->context->addViolation($constraint->message);
						break;
					}
				}
			}
		}
	}

}