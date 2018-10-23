<?php

namespace Ladb\CoreBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class EditPicture {

	/**
	 * @Assert\Length(max=255)
	 */
	private $legend;

	/**
	 * @Assert\Length(max=255)
	 * @Assert\Url()
	 */
	private $sourceUrl;

	private $rotation = 0;

	/**
	 * @Assert\Range(min=0, max=100)
	 */
	private $centerX100 = 50;

	/**
	 * @Assert\Range(min=0, max=100)
	 */
	private $centerY100 = 50;

	// Legend /////

	public function setLegend($body) {
		$this->legend = $body;
		return $this;
	}

	public function getLegend() {
		return $this->legend;
	}

	// SourceUrl /////

	public function getSourceUrl() {
		return $this->sourceUrl;
	}

	public function setSourceUrl($sourceUrl) {
		$this->sourceUrl = $sourceUrl;
		return $this;
	}

	// Rotation /////

	public function setRotation($rotation) {
		$this->rotation = $rotation;
		return $this;
	}

	public function getRotation() {
		return $this->rotation;
	}

	// CenterX100 /////

	public function setCenterX100($centerX100) {
		$this->centerX100 = $centerX100;
		return $this;
	}

	public function getCenterX100() {
		return $this->centerX100;
	}

	// CenterY100 /////

	public function setCenterY100($centerY100) {
		$this->centerY100 = $centerY100;
		return $this;
	}

	public function getCenterY100() {
		return $this->centerY100;
	}

}