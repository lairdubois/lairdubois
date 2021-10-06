<?php

namespace App\Repository\Collection;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Collection\Collection;
use App\Repository\AbstractEntityRepository;

class EntryRepository extends AbstractEntityRepository {

	/////

	public function existsByEntityTypeAndEntityIdAndCollection($entityType, $entityId, Collection $collection) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(e.id)' ))
			->from($this->getEntityName(), 'e')
			->where('e.entityType = :entityType')
			->andWhere('e.entityId = :entityId')
			->andWhere('e.collection = :collection')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setParameter('collection', $collection)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (NonUniqueResultException $e) {
			return false;
		}
	}

	/////

	public function findOneByEntityTypeAndEntityIdAndCollection($entityType, $entityId, Collection $collection) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($this->getEntityName(), 'e')
			->where('e.entityType = :entityType')
			->andWhere('e.entityId = :entityId')
			->andWhere('e.collection = :collection')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setParameter('collection', $collection)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (NoResultException $e) {
			return null;
		} catch (NonUniqueResultException $e) {
			return null;
		}
	}

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($this->getEntityName(), 'e')
			->where('e.entityType = :entityType')
			->andWhere('e.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
		;

		return $queryBuilder->getQuery()->getResult();
	}

	/////

	public function findPaginedByEntityTypeAndCollection($entityType, Collection $collection, $offset, $limit) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($this->getEntityName(), 'e')
			->where('e.entityType = :entityType')
			->andWhere('e.collection = :collection')
			->setParameter('entityType', $entityType)
			->setParameter('collection', $collection)
			->orderBy('e.createdAt', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		return new Paginator($queryBuilder->getQuery());
	}

}