<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ArticleBody extends Constraint {

	public function validatedBy() {
		return 'ladb_core.article_body_validator';
	}

	public function getTargets() {
		return self::CLASS_CONSTRAINT;
	}

}