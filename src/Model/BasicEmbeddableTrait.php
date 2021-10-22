<?php

namespace App\Model;

trait BasicEmbeddableTrait {

	// Sticker /////

	public function setSticker(\App\Entity\Core\Picture $sticker = null) {
		$this->sticker = $sticker;
		return $this;
	}

	public function getSticker() {
		return $this->sticker;
	}

}