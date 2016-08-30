<?php

namespace Ladb\CoreBundle\Form\Model;

class NewProvider {

	/**
	 */
	private $signValue;

	/**
	 */
	private $logoValue;

	// SignValue /////

	public function setSignValue($signValue) {
		$this->signValue = $signValue;
		return $this;
	}

	public function getSignValue() {
		return $this->signValue;
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