<?php

namespace App\Model;

trait PicturedTrait {

	// MainPicture /////

	public function setMainPicture(\App\Entity\Core\Picture $mainPicture = null) {
		$this->mainPicture = $mainPicture;
		return $this;
	}

	public function getMainPicture() {
		return $this->mainPicture;
	}

}