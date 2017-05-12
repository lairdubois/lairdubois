<?php

namespace Ladb\CoreBundle\Model;

trait ScrapableTrait {

	// IsScrapable /////

	public function getIsScrapable() {
		return $this instanceof ViewableInterface ? $this->getIsViewable() : true;
	}

}