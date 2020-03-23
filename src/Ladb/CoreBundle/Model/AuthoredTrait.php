<?php

namespace Ladb\CoreBundle\Model;

trait AuthoredTrait {

	// User /////

	public function getUser() {
		return $this->user;
	}

	public function setUser(\Ladb\CoreBundle\Entity\Core\User $user) {
		$this->user = $user;
		return $this;
	}

	// IsOwner /////

	public function getIsOwner(\Ladb\CoreBundle\Entity\Core\User $user = null) {
		return $user == $this->getUser();
	}

}