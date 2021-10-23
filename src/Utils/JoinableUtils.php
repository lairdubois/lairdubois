<?php

namespace App\Utils;

use App\Entity\Core\Join;
use App\Model\DraftableInterface;
use App\Model\JoinableInterface;
use App\Model\AuthoredInterface;
use App\Model\ViewableInterface;
use App\Entity\Core\User;

class JoinableUtils extends AbstractContainerAwareUtils {

	public function deleteJoins(JoinableInterface $joinable, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$joinRepository = $om->getRepository(Join::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::class);

		$joins = $joinRepository->findByEntityTypeAndEntityId($joinable->getType(), $joinable->getId());
		foreach ($joins as $join) {
			$activityUtils->deleteActivitiesByJoin($join);
			$om->remove($join);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function deleteJoinsByUser(User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$joinRepository = $om->getRepository(Join::CLASS_NAME);
		$typableUtils = $this->get(TypableUtils::class);
		$activityUtils = $this->get(ActivityUtils::class);

		$joins = $joinRepository->findByUser($user);
		foreach ($joins as $join) {
			$joinable = $typableUtils->findTypable($join->getEntityType(), $join->getEntityId());
			if (!is_null($joinable) && $joinable instanceof AuthoredInterface) {
				$joinable->getUser()->getMeta()->incrementJoinCount(-1);
			}
			$activityUtils->deleteActivitiesByJoin($join);
			$om->remove($join);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function incrementUsersJoinCount(JoinableInterface $joinable, $by = 1, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$joinRepository = $om->getRepository(Join::CLASS_NAME);

		$joins = $joinRepository->findByEntityTypeAndEntityId($joinable->getType(), $joinable->getId());
		foreach ($joins as $join) {
			$join->getUser()->getMeta()->incrementJoinCount($by);
		}
		if ($flush) {
			$om->flush();
		}
	}

	/////

	public function getJoinContext(JoinableInterface $joinable, User $user = null) {
		$om = $this->getDoctrine()->getManager();
		$joinRepository = $om->getRepository(Join::CLASS_NAME);

		$join = null;
		if (!is_null($user)) {
			$join = $joinRepository->findOneByEntityTypeAndEntityIdAndUser($joinable->getType(), $joinable->getId(), $user);
		}
		return array(
			'id'         => is_null($join) ? null : $join->getId(),
			'entityType' => $joinable->getType(),
			'entityId'   => $joinable->getId(),
			'isJoinable' => $joinable->getIsJoinable(),
			'count'      => $joinable->getJoinCount(),
		);
	}

	/////

	public function transferJoins(JoinableInterface $joinableSrc, JoinableInterface $joinableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$joinRepository = $om->getRepository(Join::CLASS_NAME);

		// Retrieve joins
		$joins = $joinRepository->findByEntityTypeAndEntityId($joinableSrc->getType(), $joinableSrc->getId());

		// Transfer joins
		foreach ($joins as $join) {
			$join->setEntityType($joinableDest->getType());
			$join->setEntityId($joinableDest->getId());
		}

		// Update counters
		$joinableDest->incrementJoinCount($joinableSrc->getJoinCount());
		$joinableSrc->setJoinCount(0);

		if ($flush) {
			$om->flush();
		}
	}

}