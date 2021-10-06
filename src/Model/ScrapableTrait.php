<?php

namespace App\Model;

trait ScrapableTrait {

	// IsScrapable /////

	public function getIsScrapable() {
		return $this instanceof HiddableInterface ? $this->getIsPublic() : true;
	}

}