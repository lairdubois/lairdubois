<?php

namespace Ladb\CoreBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;
use Ladb\CoreBundle\Utils\TypableUtils;

class JoinRepository extends AbstractEntityRepository {

	/////

	public function existsByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(j.id)' ))
			->from($this->getEntityName(), 'j')
			->where('j.entityType = :entityType')
			->andWhere('j.entityId = :entityId')
			->andWhere('j.user = :user')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return false;
		}
	}

	/////

	public function findOneByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'j' ))
			->from($this->getEntityName(), 'j')
			->where('j.entityType = :entityType')
			->andWhere('j.entityId = :entityId')
			->andWhere('j.user = :user')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'j' ))
			->from($this->getEntityName(), 'j')
			->where('j.entityType = :entityType')
			->andWhere('j.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'j' ))
			->from($this->getEntityName(), 'j')
			->where('j.user = :user')
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPaginedByEntityTypeAndEntityIdJoinedOnUser($entityType, $entityId, $offset, $limit) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'j', 'u' ))
			->from($this->getEntityName(), 'j')
			->innerJoin('j.user', 'u')
			->where('j.entityType = :entityType')
			->andWhere('j.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		return new Paginator($queryBuilder->getQuery());
	}

}