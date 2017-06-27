<?php

namespace Ladb\CoreBundle\Model;

trait PicturedTrait {

	// MainPicture /////

	public function setMainPicture(\Ladb\CoreBundle\Entity\Core\Picture $mainPicture = null) {
		$this->mainPicture = $mainPicture;
		return $this;
	}

	public function getMainPicture() {
		return $this->mainPicture;
	}

}