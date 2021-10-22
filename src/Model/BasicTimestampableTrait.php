<?php

namespace App\Model;

trait BasicTimestampableTrait {

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// Age /////

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	// UpdatedAt /////

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	// UpdatedAge /////

	public function getUpdatedAge() {
		return $this->getUpdatedAt()->diff(new \DateTime());
	}

}