<?php

namespace Ladb\CoreBundle\Form\Model;

class NewWood {

	/**
	 */
	private $nameValue;

	/**
	 */
	private $grainValue;

	// NameValue /////

	public function setNameValue($nameValue) {
		$this->nameValue = $nameValue;
		return $this;
	}

	public function getNameValue() {
		return $this->nameValue;
	}

	// GrainValue /////

	public function setGrainValue($grainValue) {
		$this->grainValue = $grainValue;
		return $this;
	}

	public function getGrainValue() {
		return $this->grainValue;
	}

}