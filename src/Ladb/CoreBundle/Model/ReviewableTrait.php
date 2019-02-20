<?php

namespace Ladb\CoreBundle\Model;

trait ReviewableTrait {

	// ReviewCount /////

	public function incrementReviewCount($by = 1) {
		return $this->reviewCount += intval($by);
	}

	public function setReviewCount($reviewCount) {
		$this->reviewCount = $reviewCount;
		return $this;
	}

	public function getReviewCount() {
		return $this->reviewCount;
	}

	// AverageRating /////

	public function setAverageRating($averageRating) {
		$this->averageRating = $averageRating;
		return $this;
	}

	public function getAverageRating() {
		return $this->averageRating;
	}

}