<?php

namespace Ladb\CoreBundle\Repository\Core;

use Doctrine\ORM\NonUniqueResultException;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class ReviewRepository extends AbstractEntityRepository {

	/////

	public function existsByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(r.id)' ))
			->from($this->getEntityName(), 'r')
			->where('r.entityType = :entityType')
			->andWhere('r.entityId = :entityId')
			->andWhere('r.user = :user')
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
			->select(array( 'r', 'u' ))
			->from($this->getEntityName(), 'r')
			->innerJoin('r.user', 'u')
			->where('r.entityType = :entityType')
			->andWhere('r.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->orderBy('r.createdAt', 'ASC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}