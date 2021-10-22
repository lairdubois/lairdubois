<?php

namespace App\Model;

trait StripableTrait {

// Strip /////

	public function setStrip(\App\Entity\Core\Picture $strip = null) {
		$this->strip = $strip;
		return $this;
	}

	public function getStrip() {
		return $this->strip;
	}

}

