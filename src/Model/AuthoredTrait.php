<?php

namespace App\Model;

trait AuthoredTrait {

	// User /////

	public function getUser() {
		return $this->user;
	}

	public function setUser(\App\Entity\Core\User $user) {
		$this->user = $user;
		return $this;
	}

	// IsOwner /////

	public function getIsOwner(\App\Entity\Core\User $user = null) {
		return $user == $this->getUser();
	}

}