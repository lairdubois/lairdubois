<?php

namespace App\Fos;

use FOS\UserBundle\Model\UserInterface;
use App\Entity\Core\User;
use App\Utils\MailerUtils;

class UserManager {

	const NAME = 'ladb_core.fos.user_manager';

	public function findUserByDisplayname($displayname) {
		return $this->findUserBy(array('displaynameCanonical' => $this->getCanonicalFieldsUpdater()->canonicalizeUsername($displayname)));
	}

    public function findUserByUsername($username)
    {
        return null;
	}

	public function updateUser(UserInterface $user, $andFlush = true) {

		// Populate displayname if null
		if ($user instanceof User && is_null($user->getDisplayname())) {
			$user->setDisplayname($user->getUsername());
		}

		parent::updateUser($user, $andFlush);
	}

}