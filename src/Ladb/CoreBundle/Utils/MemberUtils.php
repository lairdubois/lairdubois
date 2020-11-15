<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Model\TypableInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Entity\Core\User;

class MemberUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.member_utils';

	public function create(User $team, User $user) {

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		if (!$memberRepository->existsByTeamIdAndUser($team->getId(), $user) && $team !== $user) {

			$member = new Member();
			$member->setTeam($team);
			$member->setUser($user);

			$om->persist($member);

			// Update counters

			$user->getMeta()->incrementTeamCount();
			$team->getMeta()->incrementMemberCount();

			$om->flush();

		}

	}

	public function delete(User $team, User $user) {

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		$member = $memberRepository->findOneByTeamIdAndUser($team->getId(), $user);
		if (!is_null($member)) {

			$om->remove($member);

			// Update counters

			$user->getMeta()->incrementTeamCount(-1);
			$team->getMeta()->incrementMemberCount(-1);

			$om->flush();

		}

	}

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