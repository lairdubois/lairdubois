<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\DraftableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Model\PublicationInterface;
use Symfony\Component\HttpFoundation\Request;

trait PublicationControllerTrait {

	use UserControllerTrait;

	// Rights /////

	protected function getPermissionContext(PublicationInterface $publication) {

		$isAdmin = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
		$isOwner = $publication instanceof AuthoredInterface && $publication->getUser() == $this->getUser();
		$isOwnerMember = $this->get('security.authorization_checker')->isGranted('ROLE_USER') && $publication instanceof AuthoredInterface && $this->getDoctrine()->getManager()->getRepository(Member::class)->existsByTeamIdAndUser($publication->getUser()->getId(), $this->getUser());
		$isPublic = $publication instanceof HiddableInterface && $publication->getIsPublic() || true;

		return array(
			'isAdmin'       => $isAdmin,
			'isOwner'       => $isOwner,
			'isOwnerMember' => $isOwnerMember,

			'editable'      => $isAdmin || $isOwner || $isOwnerMember,
			'lockable'      => $isAdmin && !$publication->getIsLocked(),
			'unlockable'    => $isAdmin && $publication->getIsLocked(),
			'publishable'   => ($isAdmin || $isOwner || $isOwnerMember) && !$isPublic,
			'unpublishable' => $isAdmin && $isPublic,
			'deletable'     => $isAdmin || ($isOwner && !$isPublic),
		);
	}

	// Data /////

	protected function retrieveOwner(Request $request) {

		// Try to get owner user from parameters and returns it if it exists (and if it's a team) else return session user
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

		// Return session user
		return $this->getUser();
	}

	protected function retrievePublication($id, $className) {
		$om = $this->getDoctrine()->getManager();
		$publicationRepository = $om->getRepository($className);
		$publication = $publicationRepository->findOneById($id);
		if (is_null($publication)) {
			throw $this->createNotFoundException('Unable to find '.$className.' entity (id='.$id.').');
		}
		return $publication;
	}

	// Asserts /////

	protected function assertAdminGranted($context = '') {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed ('.$context.')');
		}
	}

	protected function assertWriteAccessGranted(PublicationInterface $publication, $context = '', $checkWitness = false) {

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
					return $response;	// TODO
				}
			}
			throw $this->createNotFoundException('Not allowed ('.$context.')');
		}

		return true;
	}

	protected function assertEditabable(PublicationInterface $publication, $context = '') {
		$this->assertWriteAccessGranted($publication, $context);
	}

	protected function assertLockUnlockable(PublicationInterface $publication, $lock, $context = '') {
		$this->assertAdminGranted($context);
		if ($publication->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked ('.$context.')');
		}
	}

	protected function assertPublishable(PublicationInterface $publication, $context = '') {
		$this->assertWriteAccessGranted($publication, $context);
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not emailConfirmed ('.$context.')');
		}
		if ($publication instanceof DraftableInterface && $publication->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published ('.$context.')');
		}
		if ($publication->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked ('.$context.')');
		}
	}

	protected function assertUnpublishable(PublicationInterface $publication, $ownerAllowed = false, $context = '') {
		if (!$ownerAllowed) {
			$this->assertAdminGranted($context);
		}
		if ($publication instanceof DraftableInterface && $publication->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft ('.$context.')');
		}
	}

	protected function assertDeletable(PublicationInterface $publication, $ownerAllowed = false, $context = '') {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !(($publication->getIsDraft() === true || $ownerAllowed) && $publication->getUser()->getId() == $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed ('.$context.')');
		}
	}

	protected function assertShowable(PublicationInterface $publication, $publicly = false, $context = '') {
		if ($publication instanceof DraftableInterface && $publication->getIsDraft() === true) {
			if ($publicly) {
				throw $this->createNotFoundException('Not allowed ('.$context.')');
			}
			$this->assertWriteAccessGranted($publication, $context, true);
		}
	}

}