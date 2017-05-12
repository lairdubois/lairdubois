<?php

namespace Ladb\CoreBundle\Model;

trait IndexableTrait {

	// IsIndexable /////

	public function isIndexable() {
		return $this instanceof DraftableInterface ? $this->getIsDraft() !== true : true;
	}

}