<?php

namespace Ladb\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FindBodyBlocks extends BodyBlocks {

	public function validatedBy() {
		return 'ladb_core.find_body_blocks_validator';
	}

}