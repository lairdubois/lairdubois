<?php

namespace App\Form\Model;

use App\Entity\Knowledge\Value\Picture;
use App\Entity\Knowledge\Value\Text;

class NewSchool {

	/**
	 */
	private $nameValue;

	/**
	 */
	private $logoValue;

	// NameValue /////

	public function setNameValue(Text $nameValue) {
		$this->nameValue = $nameValue;
		return $this;
	}

	public function getNameValue() {
		return $this->nameValue;
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