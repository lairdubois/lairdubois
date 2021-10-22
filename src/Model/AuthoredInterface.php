<?php

namespace App\Model;

interface AuthoredInterface {

	// User /////

	public function getUser();

	// IsOwner /////

	public function getIsOwner(\App\Entity\Core\User $user = null);

}
