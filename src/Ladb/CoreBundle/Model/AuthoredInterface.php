<?php

namespace Ladb\CoreBundle\Model;

interface AuthoredInterface {

	// User /////

	public function getUser();

	// IsOwner /////

	public function getIsOwner(\Ladb\CoreBundle\Entity\Core\User $user = null);

}
