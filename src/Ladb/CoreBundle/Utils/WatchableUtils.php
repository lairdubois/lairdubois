<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Watch;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Entity\User;

class WatchableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.watchable_utils';

    public function autoCreateWatch(WatchableInterface $watchable, User $user) {
        if ($user->getAutoWatchEnabled()) {
            return $this->createWatch($watchable, $user);
        }
        return false;
    }

    public function createWatch(WatchableInterface $watchable, User $user) {
		$om = $this->getDoctrine()->getManager();
		$watchRepository = $om->getRepository(Watch::CLASS_NAME);

		if (!$watchRepository->existsByEntityTypeAndEntityIdAndUser($watchable->getType(), $watchable->getId(), $user)) {

            $watchable->incrementWatchCount();

            $watch = new Watch();
            $watch->setEntityType($watchable->getType());
            $watch->setEntityId($watchable->getId());
            $watch->setUser($user);

			$om->persist($watch);
			$om->flush();

            return true;
        }
        return false;
    }

	public function deleteWatches(WatchableInterface $watchable, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$watchRepository = $om->getRepository(Watch::CLASS_NAME);

		$watches = $watchRepository->findByEntityTypeAndEntityId($watchable->getType(), $watchable->getId());
		foreach ($watches as $watch) {
			$om->remove($watch);
		}
		if ($flush) {
			$om->flush();
		}
	}

	/////

	public function getWatchContext(WatchableInterface $watchable, User $user = null) {
		$om = $this->getDoctrine()->getManager();
		$watchRepository = $om->getRepository(Watch::CLASS_NAME);
		$watch = null;

		if (!is_null($user)) {
			$watch = $watchRepository->findOneByEntityTypeAndEntityIdAndUser($watchable->getType(), $watchable->getId(), $user);
		}
		return array(
			'id'         => is_null($watch) ? null : $watch->getId(),
			'entityType' => $watchable->getType(),
			'entityId'   => $watchable->getId(),
			'count'      => $watchable->getWatchCount(),
		);
	}

	/////

	public function transferWatches(WatchableInterface $watchableSrc, WatchableInterface $watchableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$watchRepository = $om->getRepository(Watch::CLASS_NAME);

		// Retrieve watches
		$watches = $watchRepository->findByEntityTypeAndEntityId($watchableSrc->getType(), $watchableSrc->getId());

		// Transfer watches
		foreach ($watches as $watch) {
			$watch->setEntityType($watchableDest->getType());
			$watch->setEntityId($watchableDest->getId());
		}

		// Update counters
		$watchableDest->incrementWatchCount($watchableSrc->getWatchCount());
		$watchableSrc->setWatchCount(0);

		if ($flush) {
			$om->flush();
		}
	}

}