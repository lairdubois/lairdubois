<?php

namespace Ladb\CoreBundle\Form\Model;

class NewSchool {

	/**
	 */
	private $nameValue;

	/**
	 */
	private $logoValue;

	// NameValue /////

	public function setNameValue($nameValue) {
		$this->nameValue = $nameValue;
		return $this;
	}

	public function getNameValue() {
		return $this->nameValue;
	}

	// LogoValue /////

	public function setLogoValue($logoValue) {
		$this->logoValue = $logoValue;
		return $this;
	}

	public function getLogoValue() {
		return $this->logoValue;
	}

}