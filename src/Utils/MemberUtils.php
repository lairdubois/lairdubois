<?php

namespace App\Utils;

use Doctrine\Persistence\ObjectManager;
use App\Entity\Core\Member;
use App\Model\TypableInterface;
use App\Model\TitledInterface;
use App\Entity\Core\User;

class MemberUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.member_utils';

	public function deleteMembersByTeam(User $team, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		$members = $memberRepository->findByUser($team);
		foreach ($members as $member) {
			$member->getUser()->getMeta()->incrementTeamCount(-1);
			$member->getTeam()->getMeta()->incrementMemberCount(-1);
			$om->remove($member);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function deleteTeamsByUser(User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		$members = $memberRepository->findByTeam($user);
		foreach ($members as $member) {
			$member->getUser()->getMeta()->incrementTeamCount(-1);
			$member->getTeam()->incrementMemberCount(-1);
			$om->remove($member);
		}
		if ($flush) {
			$om->flush();
		}
	}

}