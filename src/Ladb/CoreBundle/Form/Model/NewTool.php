<?php

namespace Ladb\CoreBundle\Form\Model;

use Ladb\CoreBundle\Entity\Knowledge\Value\ToolIdentity;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;

class NewTool {

	/**
	 */
	private $identityValue;

	/**
	 */
	private $photoValue;

	// IdentityValue /////

	public function setIdentityValue(ToolIdentity $identityValue) {
		$this->identityValue = $identityValue;
		return $this;
	}

	public function getIdentityValue() {
		return $this->identityValue;
	}

	// PhotoValue /////

	public function setPhotoValue(Picture $photoValue) {
		$this->photoValue = $photoValue;
		return $this;
	}

	public function getPhotoValue() {
		return $this->photoValue;
	}

}