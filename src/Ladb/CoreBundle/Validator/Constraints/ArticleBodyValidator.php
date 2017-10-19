<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Ladb\CoreBundle\Entity\Core\Block\Text;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Ladb\CoreBundle\Entity\Howto\Article;

class ArticleBodyValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param mixed $value      The value that should be validated
	 * @param Constraint $constraint The constraint for the validation
	 *
	 * @api
	 */
	public function validate($value, Constraint $constraint) {
		if ($value instanceof Article) {
			$howto = $value->getHowto();
			if (!is_null($howto) && $value->getBodyBlocks()->count() > 0 && $value->getBodyBlocks()[0] instanceof Text) {
				$howtoBodyExtract = strtolower(trim(substr($howto->getBody(), 0, 100)));
				$articleBodyExtract = strtolower(trim(substr($value->getBodyBlocks()[0]->getBody(), 0, strlen($howtoBodyExtract))));
				if (strcmp($howtoBodyExtract, $articleBodyExtract) == 0) {
					$this->context->buildViolation('Le texte du corps de l\'article doit être différent de celui de l\'introduction du pas à pas.')
						->atPath('bodyBlocks')
						->addViolation();

				}
			}
		}
	}

}