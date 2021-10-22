<?php

namespace App\Model;

trait ViewableTrait {

	private $isShown = true;

	/////

	// ViewCount /////

	public function incrementViewCount($by = 1) {
		return $this->viewCount += intval($by);
	}

	public function setViewCount($viewCount) {
		$this->viewCount = $viewCount;
		return $this;
	}

	public function getViewCount() {
		return $this->viewCount;
	}

	// IsShown /////

	public function setIsShown($isShown) {
		$this->isShown = $isShown;
	}

	public function getIsShown() {
		return $this->isShown;
	}

}