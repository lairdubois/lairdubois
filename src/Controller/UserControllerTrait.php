<?php

namespace App\Controller;

use App\Entity\Core\UserWitness;
use App\Fos\UserManager;

trait UserControllerTrait {

	protected function retrieveUserByUsername($username) {
		$userManager = $this->get(UserManager::class);

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {

			// Try to load user witness
			$om = $this->getDoctrine()->getManager();
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