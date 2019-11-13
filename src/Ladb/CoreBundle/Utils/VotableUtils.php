<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\VotableParentInterface;
use Ladb\CoreBundle\Model\VotableInterface;
use Ladb\CoreBundle\Entity\Core\Vote;
use Ladb\CoreBundle\Entity\Core\User;

class VotableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.votable_utils';

	const DISABLE_REASON_NONE = 0;
	const DISABLE_REASON_NOT_CONNECTED = 1;
	const DISABLE_REASON_SELF_VOTING = 2;
	const DISABLE_REASON_NOT_EMAIL_CONFIRMED = 3;

	public function deleteVotes(VotableInterface $votable, VotableParentInterface $votableParent, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);

		$votes = $voteRepository->findByEntityTypeAndEntityId($votable->getType(), $votable->getId());
		foreach ($votes as $vote) {
			if ($vote->getScore() > 0) {
				$votableParent->incrementPositiveVoteCount(-1);
				$vote->getUser()->getMeta()->incrementPositiveVoteCount(-1);
			} else {
				$votableParent->incrementNegativeVoteCount(-1);
				$vote->getUser()->getMeta()->incrementNegativeVoteCount(-1);
			}
			$votableParent->incrementVoteCount(-1);
			$activityUtils->deleteActivitiesByVote($vote);
			$votable->incrementVoteCount(-1);
			$om->remove($vote);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function incrementUsersVoteCount(VotableInterface $votable, $by = 1, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);

		$votes = $voteRepository->findByEntityTypeAndEntityId($votable->getType(), $votable->getId());
		foreach ($votes as $vote) {
			if ($vote->getScore() > 0) {
				$vote->getUser()->getMeta()->incrementPositiveVoteCount($by);
			} else {
				$vote->getUser()->getMeta()->incrementNegativeVoteCount($by);
			}
		}
		if ($flush) {
			$om->flush();
		}
	}

	/////

	public function getVoteContexts($votables, User $user = null) {
		$voteContexts = array();
		foreach ($votables as $votable) {
			$voteContexts[$votable->getId()] = $this->getVoteContext($votable, $user);
		}
		return $voteContexts;
	}

	public function getVoteContext(VotableInterface $votable, User $user = null) {
		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);

		$vote = null;
		if (!is_null($user)) {
			$vote = $voteRepository->findOneByEntityTypeAndEntityIdAndUser($votable->getType(), $votable->getId(), $user);
		}
		$disableReason = self::DISABLE_REASON_NONE;
		if (is_null($user)) {
			$disableReason = self::DISABLE_REASON_NOT_CONNECTED;
		} else if ($votable instanceof AuthoredInterface && $votable->getUser()->getId() == $user->getId()) {
			$disableReason = self::DISABLE_REASON_SELF_VOTING;
		} else if (!$user->getEmailConfirmed()) {
			$disableReason = self::DISABLE_REASON_NOT_EMAIL_CONFIRMED;
		}
		return array(
			'votable'       => $votable,
			'vote'          => $vote,
			'disableReason' => $disableReason,
			'entityType'    => $votable->getType(),
			'entityId'      => $votable->getId(),
		);
	}

	/////

	public function transferVotes(VotableInterface $votableSrc, VotableInterface $votableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);

		// Retrieve votes
		$votes = $voteRepository->findByEntityTypeAndEntityId($votableSrc->getType(), $votableSrc->getId());

		// Transfer votes
		foreach ($votes as $vote) {
			$vote->setEntityType($votableDest->getType());
			$vote->setEntityId($votableDest->getId());
			$vote->setParentEntityType($votableDest->getParentEntityType());
			$vote->setParentEntityId($votableDest->getParentEntityId());
			$vote->setParentEntityField($votableDest->getParentEntityField());
		}

		// Update counters
		$votableDest->incrementPositiveVoteScore($votableSrc->getPositiveVoteScore());
		$votableDest->incrementNegativeVoteScore($votableSrc->getNegativeVoteScore());
		$votableDest->incrementVoteScore($votableSrc->getVoteScore());
		$votableDest->incrementVoteCount($votableSrc->getVoteCount());

		$votableSrc->incrementPositiveVoteScore(-$votableSrc->getPositiveVoteScore());
		$votableSrc->incrementNegativeVoteScore(-$votableSrc->getNegativeVoteScore());
		$votableSrc->incrementVoteScore(-$votableSrc->getVoteScore());
		$votableSrc->incrementVoteCount(-$votableSrc->getVoteCount());

		if ($flush) {
			$om->flush();
		}
	}

}