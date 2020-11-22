<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\DraftableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Model\PublicationInterface;
use Ladb\CoreBundle\Model\RepublishableInterface;
use Symfony\Component\HttpFoundation\Request;

trait PublicationControllerTrait {

	use UserControllerTrait;

	// Permissions /////

	protected function getPermissionContext(PublicationInterface $publication) {

		$isGrantedUser = $this->get('security.authorization_checker')->isGranted('ROLE_USER');
		$isGrantedAdmin = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
		$isOwner = $publication instanceof AuthoredInterface && $publication->getUser() == $this->getUser();
		$isOwnerMember = $isGrantedUser && $publication instanceof AuthoredInterface && $this->getDoctrine()->getManager()->getRepository(Member::class)->existsByTeamAndUser($publication->getUser(), $this->getUser());
		$isGrantedOwner = $isOwner || $isOwnerMember;
		$isPublic = $publication instanceof HiddableInterface && $publication->getIsPublic() || !($publication instanceof HiddableInterface);

		return array(
			'isGrantedUser'  => $isGrantedUser,
			'isGrantedAdmin' => $isGrantedAdmin,
			'isGrantedOwner' => $isGrantedOwner,
			'isOwner'        => $isOwner,
			'isOwnerMember'  => $isOwnerMember,

			'editable'      => $publication instanceof AuthoredInterface && ($isGrantedAdmin || $isOwner || $isOwnerMember),
			'publishable'   => ($isGrantedAdmin || $isOwner || $isOwnerMember) && !$isPublic,
			'unpublishable' => $isGrantedAdmin && $isPublic,
			'deletable'     => $isGrantedAdmin || (($isOwner || $isOwnerMember) && !$isPublic),
			'likable'       => !($isOwner || $isOwnerMember),
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
					throw $this->createNotFoundException('Owner user must be a team.');
				}

				// Only members allowed
				$om = $this->getDoctrine()->getManager();
				$memberRepository = $om->getRepository(Member::class);
				if (!$memberRepository->existsByTeamAndUser($user, $this->getUser())) {
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
		$publication = $publicationRepository->findOneById(intval($id));
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
			$allowed = $memberRepository->existsByTeamAndUser($publication->getUser(), $this->getUser());

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

	protected function assertPublishable(PublicationInterface $publication, $maxPublishCount = 0, $context = '') {
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
		if ($publication instanceof RepublishableInterface && $maxPublishCount > 0 && $publication->getPublishCount() >= $maxPublishCount) {
			throw $this->createNotFoundException('Max publish count reached ('.$context.')');
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
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {

			$isGrantedUser = $this->get('security.authorization_checker')->isGranted('ROLE_USER');
			$isOwner = $publication instanceof AuthoredInterface && $publication->getUser() == $this->getUser();
			$isOwnerMember = $isGrantedUser && $publication instanceof AuthoredInterface && $this->getDoctrine()->getManager()->getRepository(Member::class)->existsByTeamAndUser($publication->getUser(), $this->getUser());
			$isGrantedOwner = $isOwner || $isOwnerMember;

			if (!(($publication->getIsDraft() === true || $ownerAllowed) && $isGrantedOwner)) {
				throw $this->createNotFoundException('Not allowed ('.$context.')');
			}

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

	// Filters /////

	protected function pushGlobalVisibilityFilter(&$filters, $includePrivates = false, $includeTeamsPrivates = false) {

		$user = $this->getUser();
		$publicVisibilityFilter = new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC ));
		if (!is_null($user) && $includePrivates) {

			$filter = new \Elastica\Query\BoolQuery();
			$filter->addShould(
				$publicVisibilityFilter
			);
			$filter->addShould(
				(new \Elastica\Query\BoolQuery())
					->addFilter(new \Elastica\Query\MatchPhrase('user.username', $user->getUsername()))
					->addFilter(new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PRIVATE )))
			);

			if ($includeTeamsPrivates && $this->getUser()->getMeta()->getTeamCount() > 0) {

				$memberRepository = $this->getDoctrine()->getRepository(Member::CLASS_NAME);
				$members = $memberRepository->findPaginedByUser($this->getUser(), 0, 20);

				foreach ($members as $member) {
					$filter->addShould(
						(new \Elastica\Query\BoolQuery())
							->addFilter(new \Elastica\Query\MatchPhrase('user.username', $member->getTeam()->getUsername()))
							->addFilter(new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PRIVATE )))
					);
				}

			}

		} else {
			$filter = $publicVisibilityFilter;
		}
		$filters[] = $filter;

	}

}