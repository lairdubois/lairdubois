<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Entity\Core\UserWitness;
use Symfony\Component\HttpFoundation\Request;

trait CustomOwnerControllerTrait {

	use UserControllerTrait;

	/*
	 * Returns mentioned owner user or session user if not
	 */
	protected function retrieveOwner(Request $request) {

		// Try to get owner user from parameters and returns it if it exists (and if it's a team)
		$username = $request->get('owner');
		if (!is_null($username)) {
			$user = $this->retrieveUserByUsername($username);
			if (!is_null($user)) {

				// Only team allowed
				if (!$user->getIsTeam()) {
					throw $this->createNotFoundException('As user must be a team.');
				}

				// Only members allowed
				$om = $this->getDoctrine()->getManager();
				$memberRepository = $om->getRepository(Member::class);
				if (!$memberRepository->existsByTeamIdAndUser($user->getId(), $this->getUser())) {
					throw $this->createNotFoundException('Access denied');
				}

			}
			return $user;
		}

		// Return logged user
		return $this->getUser();
	}

}