<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Entity\Core\UserWitness;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\PublicationInterface;
use Symfony\Component\HttpFoundation\Request;

trait RightsControllerTrait {

	protected function checkWriteAccessOn(PublicationInterface $publication, $checkWitness = false, $context='') {

		if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			$allowed = true;
		} else if ($publication->getUser()->getIsTeam() && !is_null($this->getUser())) {

			// Only members allowed
			$om = $this->getDoctrine()->getManager();
			$memberRepository = $om->getRepository(Member::class);
			$allowed = $memberRepository->existsByTeamIdAndUser($publication->getUser()->getId(), $this->getUser());

		} else {
			$allowed = $publication->getUser() == $this->getUser();
		}

		if (!$allowed) {
			if ($checkWitness) {
				$witnessManager = $this->get(WitnessManager::NAME);
				if ($response = $witnessManager->checkResponse($publication->getType(), $publication->getId())) {
					return $response;
				}
			}
			throw $this->createNotFoundException('Not allowed ('.$context.')');
		}

	}

}