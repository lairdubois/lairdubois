<?php

namespace Ladb\CoreBundle\Form\Model;

use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;
use Ladb\CoreBundle\Entity\Knowledge\Value\SoftwareIdentity;

class NewSoftware {

	/**
	 */
	private $identityValue;

	/**
	 */
	private $iconValue;

	// IdentityValue /////

	public function setIdentityValue(SoftwareIdentity $identityValue) {
		$this->identityValue = $identityValue;
		return $this;
	}

	public function getIdentityValue() {
		return $this->identityValue;
	}

	// IconValue /////

	public function setIconValue(Picture $iconValue) {
		$this->iconValue = $iconValue;
		return $this;
	}

	public function getIconValue() {
		return $this->iconValue;
	}

}