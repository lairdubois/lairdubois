<?php

namespace App\Manager\Core;

use App\Entity\Core\Member;
use App\Manager\AbstractManager;

class MemberManager extends AbstractManager {

	public function create(\App\Entity\Core\User $team, \App\Entity\Core\User $user, $flush = true) {
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