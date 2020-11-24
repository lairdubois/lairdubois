<?php

namespace Ladb\CoreBundle\Manager\Core;

use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Entity\Core\MemberInvitation;
use Ladb\CoreBundle\Manager\AbstractManager;
use Ladb\CoreBundle\Utils\ActivityUtils;

class MemberManager extends AbstractManager {

	const NAME = 'ladb_core.member_manager';

	public function create(\Ladb\CoreBundle\Entity\Core\User $team, \Ladb\CoreBundle\Entity\Core\User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create the invitation
		$invitation = new Member();
		$invitation->setTeam($team);
		$invitation->setUser($user);

		$om->persist($invitation);

		// Update counters

		$user->getMeta()->incrementTeamCount();
		$team->getMeta()->incrementMemberCount();

		if ($flush) {
			$om->flush();
		}

		return $invitation;
	}

	public function delete(Member $member, $flush = true) {

		// Update counters

		$member->getUser()->getMeta()->incrementTeamCount(-1);
		$member->getTeam()->getMeta()->incrementMemberCount(-1);

		parent::deleteEntity($member, $flush);
	}

}