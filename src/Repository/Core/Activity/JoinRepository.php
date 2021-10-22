<?php

namespace App\Repository\Core\Activity;

use App\Repository\AbstractEntityRepository;

class JoinRepository extends AbstractEntityRepository {

	/////

	public function findByJoin(\App\Entity\Core\Join $join) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.join = :join')
			->setParameter('join', $join)
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
			->innerJoin('a.join', 'j')
			->andWhere('j.entityType = :entityType')
			->andWhere('j.entityId = :entityId')
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