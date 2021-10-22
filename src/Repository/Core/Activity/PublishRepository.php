<?php

namespace App\Repository\Core\Activity;

use App\Repository\AbstractEntityRepository;

class PublishRepository extends AbstractEntityRepository {

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.entityType = :entityType')
			->andWhere('a.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByUserAndEntityTypeAndEntityId(\App\Entity\Core\User $user, $entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->andWhere('a.user = :user')
			->andWhere('a.entityType = :entityType')
			->andWhere('a.entityId = :entityId')
			->setParameter('user', $user)
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