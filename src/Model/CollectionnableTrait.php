<?php

namespace App\Model;

trait CollectionnableTrait {

	// PrivateCollectionCount /////

	public function incrementPrivateCollectionCount($by = 1) {
		return $this->privateCollectionCount += intval($by);
	}

	public function setPrivateCollectionCount($privateCollectionCount) {
		$this->privateCollectionCount = $privateCollectionCount;
		return $this;
	}

	public function getPrivateCollectionCount() {
		return $this->privateCollectionCount;
	}

	// PublicCollectionCount /////

	public function incrementPublicCollectionCount($by = 1) {
		return $this->publicCollectionCount += intval($by);
	}

	public function setPublicCollectionCount($publicCollectionCount) {
		$this->publicCollectionCount = $publicCollectionCount;
		return $this;
	}

	public function getPublicCollectionCount() {
		return $this->publicCollectionCount;
	}

}