<?php

namespace App\Model;

trait DraftableTrait {

	// IsDraft /////

	public function setIsDraft($isDraft) {
		$this->isDraft = $isDraft;
		return $this;
	}

	public function getIsDraft() {
		return $this->isDraft;
	}

}