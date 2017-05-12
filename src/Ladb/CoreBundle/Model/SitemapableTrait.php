<?php

namespace Ladb\CoreBundle\Model;

trait SitemapableTrait {

	// IsSitemapable /////

	public function getIsSitemapable() {
		return $this instanceof IndexableInterface ? $this->isIndexable() !== true : true;
	}

}