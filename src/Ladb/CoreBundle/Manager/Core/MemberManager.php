<?php

namespace Ladb\CoreBundle\Manager\Core;

use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Manager\AbstractManager;

class MemberManager extends AbstractManager {

	const NAME = 'ladb_core.core_member_manager';

	public function create(\Ladb\CoreBundle\Entity\Core\User $team, \Ladb\CoreBundle\Entity\Core\User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create the invitation
		$member = new Member();
		$member->setTeam($team);
		$member->setUser($user);

		$om->persist($member);

		// Update counters

		$user->getMeta()->incrementTeamCount();
		$team->getMeta()->incrementMemberCount();

		if ($flush) {
			$om->flush();
		}

		return $member;
	}

	public function delete(Member $member, $flush = true) {

		// Update counters

		$member->getUser()->getMeta()->incrementTeamCount(-1);
		$member->getTeam()->getMeta()->incrementMemberCount(-1);

		parent::deleteEntity($member, $flush);
	}

}