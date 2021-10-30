<?php

namespace App\Repository\Core\Activity;

use App\Entity\AbstractAuthoredPublication;
use App\Entity\Core\Activity\Contribute;
use App\Entity\Core\Activity\Publish;
use App\Entity\Core\Activity\Join;
use App\Entity\Core\Activity\Feedback;
use App\Model\FeedbackableInterface;
use App\Model\JoinableInterface;
use App\Repository\AbstractEntityRepository;

class ActivityRepository extends AbstractEntityRepository {

	/////

	public function findByPendingNotifications() {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->innerJoin('a.user', 'u')
			->where('a.isPendingNotifications = 1')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByPublication(\App\Entity\AbstractPublication $publication) {

		$activities = array();

		if ($publication instanceof AbstractAuthoredPublication) {

			// Publish activities /////

			$publishRepository = $this->getEntityManager()->getRepository(Publish::class);

			$activities = array_merge($activities, $publishRepository->findByEntityTypeAndEntityId($publication->getType(), $publication->getId()));
			if (!is_null($publication->getSubPublications())) {
				foreach ($publication->getSubPublications() as $subPublication) {
					$activities = array_merge($activities, $publishRepository->findByEntityTypeAndEntityId($subPublication->getType(), $subPublication->getId()));
				}

				usort($activities, function($a, $b) {
					if ($a->getCreatedAt() == $b->getCreatedAt()) {
						return 0;
					}
					return ($a->getCreatedAt() < $b->getCreatedAt()) ? -1 : 1;
				});

			}

		}

		if ($publication instanceof JoinableInterface) {

			// Join activities /////

			$joinRepository = $this->getEntityManager()->getRepository(Join::class);

			$activities = array_merge($activities, $joinRepository->findByEntityTypeAndEntityId($publication->getType(), $publication->getId()));
			if (!is_null($publication->getSubPublications())) {
				foreach ($publication->getSubPublications() as $subPublication) {
					$activities = array_merge($activities, $joinRepository->findByEntityTypeAndEntityId($subPublication->getType(), $subPublication->getId()));
				}

				usort($activities, function ($a, $b) {
					if ($a->getCreatedAt() == $b->getCreatedAt()) {
						return 0;
					}
					return ($a->getCreatedAt() < $b->getCreatedAt()) ? -1 : 1;
				});

			}

		}

		if ($publication instanceof FeedbackableInterface) {

			// Feedback activities /////

			$feedbackRepository = $this->getEntityManager()->getRepository(Feedback::class);

			$activities = array_merge($activities, $feedbackRepository->findByEntityTypeAndEntityId($publication->getType(), $publication->getId()));
			if (!is_null($publication->getSubPublications())) {
				foreach ($publication->getSubPublications() as $subPublication) {
					$activities = array_merge($activities, $feedbackRepository->findByEntityTypeAndEntityId($subPublication->getType(), $subPublication->getId()));
				}

				usort($activities, function ($a, $b) {
					if ($a->getCreatedAt() == $b->getCreatedAt()) {
						return 0;
					}
					return ($a->getCreatedAt() < $b->getCreatedAt()) ? -1 : 1;
				});

			}

		}

		// Contribute activities /////

		$contributeRepository = $this->getEntityManager()->getRepository(Contribute::class);

		$activities = array_merge($activities, $contributeRepository->findByEntityTypeAndEntityId($publication->getType(), $publication->getId()));
		if (!is_null($publication->getSubPublications())) {
			foreach ($publication->getSubPublications() as $subPublication) {
				$activities = array_merge($activities, $contributeRepository->findByEntityTypeAndEntityId($subPublication->getType(), $subPublication->getId()));
			}

			usort($activities, function($a, $b) {
				if ($a->getCreatedAt() == $b->getCreatedAt()) {
					return 0;
				}
				return ($a->getCreatedAt() < $b->getCreatedAt()) ? -1 : 1;
			});

		}

		return $activities;
	}

}