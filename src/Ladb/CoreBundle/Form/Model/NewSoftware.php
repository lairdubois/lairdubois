<?php

namespace Ladb\CoreBundle\Form\Model;

class NewSoftware {

	/**
	 */
	private $applicationValue;

	/**
	 */
	private $iconValue;

	// ApplicationValue /////

	public function getApplicationValue() {
		return $this->applicationValue;
	}

	public function setApplicationValue($applicationValue) {
		$this->applicationValue = $applicationValue;
		return $this;
	}

	// IconValue /////

	public function getIconValue() {
		return $this->iconValue;
	}

	public function setIconValue($iconValue) {
		$this->iconValue = $iconValue;
		return $this;
	}

}