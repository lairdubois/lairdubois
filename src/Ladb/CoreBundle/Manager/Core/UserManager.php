<?php

namespace Ladb\CoreBundle\Manager\Core;

use Ladb\CoreBundle\Entity\Core\UserWitness;
use Ladb\CoreBundle\Manager\AbstractManager;

class UserManager extends AbstractManager {

	const NAME = 'ladb_core.user_manager';

	public function findUserByUsername($username) {
		$userManager = $this->get('fos_user.user_manager');
		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {

			// Try to load user witness
			$om = $this->getDoctrine()->getManager();
			$userWitnessRepository = $om->getRepository(UserWitness::class);
			$userWitness = $userWitnessRepository->findOneByUsername($username);
			if (is_null($userWitness) || is_null($userWitness->getUser())) {
				return null;
			}

			return $userWitness->getUser();

		}
		return $user;
	}


}