<?php

namespace Ladb\CoreBundle\Repository\Core\Activity;

use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;
use Ladb\CoreBundle\Entity\Core\Activity\Contribute;
use Ladb\CoreBundle\Entity\Core\Activity\Publish;
use Ladb\CoreBundle\Entity\Core\Activity\Join;
use Ladb\CoreBundle\Entity\Core\Activity\Feedback;
use Ladb\CoreBundle\Model\FeedbackableInterface;
use Ladb\CoreBundle\Model\JoinableInterface;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

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

	public function findByPublication(\Ladb\CoreBundle\Entity\AbstractPublication $publication) {

		$activities = array();

		if ($publication instanceof AbstractAuthoredPublication) {

			// Publish activities /////

			$publishRepository = $this->getEntityManager()->getRepository(Publish::CLASS_NAME);

			$activities = array_merge($activities, $publishRepository->findByUserAndEntityTypeAndEntityId($publication->getUser(), $publication->getType(), $publication->getId()));
			if (!is_null($publication->getSubPublications())) {
				foreach ($publication->getSubPublications() as $subPublication) {
					$activities = array_merge($activities, $publishRepository->findByUserAndEntityTypeAndEntityId($publication->getUser(), $subPublication->getType(), $subPublication->getId()));
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

			$joinRepository = $this->getEntityManager()->getRepository(Join::CLASS_NAME);

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

			$feedbackRepository = $this->getEntityManager()->getRepository(Feedback::CLASS_NAME);

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

		$contributeRepository = $this->getEntityManager()->getRepository(Contribute::CLASS_NAME);

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