<?php

namespace Ladb\CoreBundle\Repository\Core;

use Doctrine\ORM\NonUniqueResultException;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class FeedbackRepository extends AbstractEntityRepository {

	/////

	public function existsByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(f.id)' ))
			->from($this->getEntityName(), 'f')
			->where('f.entityType = :entityType')
			->andWhere('f.entityId = :entityId')
			->andWhere('f.user = :user')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (NonUniqueResultException $e) {
			return false;
		}
	}

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->where('f.entityType = :entityType')
			->andWhere('f.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->orderBy('f.createdAt', 'DESC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}