<?php

namespace App\Form\Model;

use App\Entity\Knowledge\Value\BookIdentity;
use App\Entity\Knowledge\Value\Picture;

class NewBook {

	/**
	 */
	private $identityValue;

	/**
	 */
	private $coverValue;

	// IdentityValue /////

	public function setIdentityValue(BookIdentity $identityValue) {
		$this->identityValue = $identityValue;
		return $this;
	}

	public function getIdentityValue() {
		return $this->identityValue;
	}

	// CoverValue /////

	public function setCoverValue(Picture $coverValue) {
		$this->coverValue = $coverValue;
		return $this;
	}

	public function getCoverValue() {
		return $this->coverValue;
	}

}