<?php

namespace App\Model;

trait SitemapableTrait {

	// IsSitemapable /////

	public function getIsSitemapable() {
		if ($this instanceof HiddableInterface) {
			return $this->getIsPublic();
		}
		if ($this instanceof IndexableInterface) {
			return $this->isIndexable();
		}
		return true;
	}

}