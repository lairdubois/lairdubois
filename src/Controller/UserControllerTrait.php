<?php

namespace App\Controller;

use App\Entity\Core\User;
use App\Entity\Core\UserWitness;

trait UserControllerTrait {

	protected function retrieveUserByUsername($username) {
        $om = $this->getDoctrine()->getManager();
        $userRepository = $om->getRepository(User::CLASS_NAME);

		$user = $userRepository->findOneByUsername($username);
		if (is_null($user)) {

			// Try to load user witness
			$userWitnessRepository = $om->getRepository(UserWitness::class);
			$userWitness = $userWitnessRepository->findOneByUsername($username);
			if (is_null($userWitness) || is_null($userWitness->getUser())) {
				throw $this->createNotFoundException('User not found (username='.$username.')');
			}

			$user = $userWitness->getUser();

		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled (username='.$username.')');
		}

		return $user;
	}

}