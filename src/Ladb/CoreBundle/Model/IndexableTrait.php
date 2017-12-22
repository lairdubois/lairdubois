<?php

namespace Ladb\CoreBundle\Model;

use Ladb\CoreBundle\Entity\AbstractPublication;

trait IndexableTrait {

	// IsIndexable /////

	public function isIndexable() {
		return true;
	}

}