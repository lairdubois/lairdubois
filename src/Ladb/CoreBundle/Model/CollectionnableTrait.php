<?php

namespace Ladb\CoreBundle\Model;

trait CollectionnableTrait {

	// CollectionCount /////

	public function incrementCollectionCount($by = 1) {
		return $this->collectionCount += intval($by);
	}

	public function setCollectionCount($collectionCount) {
		$this->collectionCount = $collectionCount;
		return $this;
	}

	public function getCollectionCount() {
		return $this->collectionCount;
	}

}