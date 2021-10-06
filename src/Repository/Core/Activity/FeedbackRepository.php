<?php

namespace App\Repository\Core\Activity;

use App\Repository\AbstractEntityRepository;

class FeedbackRepository extends AbstractEntityRepository {

	/////

	public function findByFeedback(\App\Entity\Core\Feedback $feedback) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.feedback = :feedback')
			->setParameter('feedback', $feedback)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->innerJoin('a.feedback', 'f')
			->andWhere('f.entityType = :entityType')
			->andWhere('f.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->orderBy('a.createdAt', 'ASC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}