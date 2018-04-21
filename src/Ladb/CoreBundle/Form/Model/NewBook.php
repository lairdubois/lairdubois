<?php

namespace Ladb\CoreBundle\Form\Model;

class NewBook {

	/**
	 */
	private $titleValue;

	/**
	 */
	private $coverValue;

	// NameValue /////

	public function getTitleValue() {
		return $this->titleValue;
	}

	public function setTitleValue($titleValue) {
		$this->titleValue = $titleValue;
		return $this;
	}

	// GrainValue /////

	public function getCoverValue() {
		return $this->coverValue;
	}

	public function setCoverValue($coverValue) {
		$this->coverValue = $coverValue;
		return $this;
	}

}