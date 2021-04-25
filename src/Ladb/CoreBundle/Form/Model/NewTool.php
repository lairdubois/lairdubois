<?php

namespace Ladb\CoreBundle\Form\Model;

use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;

class NewTool {

	/**
	 */
	private $nameValue;

	/**
	 */
	private $photoValue;

	/**
	 */
	private $productNameValue;

	/**
	 */
	private $brandValue;

	// NameValue /////

	public function setNameValue(Text $nameValue) {
		$this->nameValue = $nameValue;
		return $this;
	}

	public function getNameValue() {
		return $this->nameValue;
	}

	// PhotoValue /////

	public function setPhotoValue(Picture $photoValue) {
		$this->photoValue = $photoValue;
		return $this;
	}

	public function getPhotoValue() {
		return $this->photoValue;
	}

	// ProductNameValue /////

	public function setProductNameValue(Text $productNameValue) {
		$this->productNameValue = $productNameValue;
		return $this;
	}

	public function getProductNameValue() {
		return $this->productNameValue;
	}

	// BrandValue /////

	public function setBrandValue(Text $brandValue) {
		$this->brandValue = $brandValue;
		return $this;
	}

	public function getBrandValue() {
		return $this->brandValue;
	}

}