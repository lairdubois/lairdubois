<?php

namespace Ladb\CoreBundle\Form\Model;

use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;

class NewBook {

	/**
	 */
	private $titleValue;

	/**
	 */
	private $coverValue;

	// NameValue /////

	public function setTitleValue(Text $titleValue) {
		$this->titleValue = $titleValue;
		return $this;
	}

	public function getTitleValue() {
		return $this->titleValue;
	}

	// GrainValue /////

	public function setCoverValue(Picture $coverValue) {
		$this->coverValue = $coverValue;
		return $this;
	}

	public function getCoverValue() {
		return $this->coverValue;
	}

}