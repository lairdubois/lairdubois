<?php

namespace App\Form\Model;

use App\Entity\Knowledge\Value\Picture;
use App\Entity\Knowledge\Value\Sign;

class NewProvider {

	/**
	 */
	private $signValue;

	/**
	 */
	private $logoValue;

	// SignValue /////

	public function setSignValue(Sign $signValue) {
		$this->signValue = $signValue;
		return $this;
	}

	public function getSignValue() {
		return $this->signValue;
	}

	// LogoValue /////

	public function setLogoValue(Picture $logoValue) {
		$this->logoValue = $logoValue;
		return $this;
	}

	public function getLogoValue() {
		return $this->logoValue;
	}

}