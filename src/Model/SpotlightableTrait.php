<?php

namespace App\Model;

trait SpotlightableTrait {

	// Spotlight /////

	public function getSpotlight() {
		return $this->spotlight;
	}

	public function setSpotlight(\App\Entity\Core\Spotlight $spotlight = null) {
		$this->spotlight = $spotlight;
		return $this;
	}

	public function withEnabledSpotlight() {
		return !is_null($this->spotlight) && $this->spotlight->getEnabled();
	}
}
