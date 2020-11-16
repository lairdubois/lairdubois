<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Entity\Core\UserWitness;
use Symfony\Component\HttpFoundation\Request;

trait UserControllerTrait {

	protected function retrieveUserByUsername($username) {
		$userManager = $this->get('fos_user.user_manager');

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

	protected function getAsUser(Request $request) {

		$as = $request->get('as');
		if (!is_null($as)) {
			$asUser = $this->retrieveUserByUsername($as);
			if (!is_null($asUser)) {

				// Only team allowed
				if (!$asUser->getIsTeam()) {
					throw $this->createNotFoundException('As user must be a team.');
				}

				// Only members allowed
				$om = $this->getDoctrine()->getManager();
				$memberRepository = $om->getRepository(Member::class);
				if (!$memberRepository->existsByTeamIdAndUser($asUser->getId(), $this->getUser())) {
					throw $this->createNotFoundException('Access denied');
				}

			}
			return $asUser;
		}

		return null;
	}

}