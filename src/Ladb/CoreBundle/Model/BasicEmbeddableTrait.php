<?php

namespace Ladb\CoreBundle\Model;

trait BasicEmbeddableTrait {

	// Sticker /////

	public function setSticker(\Ladb\CoreBundle\Entity\Picture $sticker = null) {
		$this->sticker = $sticker;
		return $this;
	}

	public function getSticker() {
		return $this->sticker;
	}

}