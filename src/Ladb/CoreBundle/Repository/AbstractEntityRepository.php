<?php

namespace Ladb\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

abstract class AbstractEntityRepository extends EntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array();
	}

	/////

	public function countNewerByDate($date, $andWheres = null, $parameters = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(e.id)' ))
			->from($this->getEntityName(), 'e')
			->where('e.changedAt > :date')
			->setParameter('date', $date)
		;
		if (!is_null($andWheres)) {
			foreach ($andWheres as $andWhere) {
				$queryBuilder->andWhere($andWhere);
			}
		}
		if (!is_null($parameters)) {
			foreach ($parameters as $key => $value) {
				$queryBuilder->setParameter($key, $value);
			}
		}
		try {
			return $queryBuilder->getQuery()->getSingleScalarResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return 0;
		}

	}

	/////

	public function findOneById($id) {
		return $this->find($id);
	}

	public function findOneByIdJoinedOn($id, array $joinOptions /* [ [ 0 = type (inner, left), 1 = join, 2 = alias ], [...] ] */) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($this->getEntityName(), 'e')
			->where('e.id = :id')
			->setParameter('id', $id)
		;

		foreach ($joinOptions as $joinOption) {
			$type = $joinOption[0];
			$join = $joinOption[1];
			$alias = $joinOption[2];
			$queryBuilder->addSelect($alias);
			if ($type == 'inner') {
				$queryBuilder->innerJoin('e.'.$join, $alias);
			} else if ($type == 'left') {
				$queryBuilder->leftJoin('e.'.$join, $alias);
			} else {
				$queryBuilder->join('e.'.$join, $alias);
			}
		}

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findLastCreated() {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($this->getEntityName(), 'e')
			->orderBy('e.createdAt', 'DESC')
			->setMaxResults(1)
		;
		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findLastUpdated() {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($this->getEntityName(), 'e')
			->orderBy('e.updatedAt', 'DESC')
			->setMaxResults(1)
		;
		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByIds(array $ids) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($this->getEntityName(), 'e')
			->where($queryBuilder->expr()->in('e.id', $ids))
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}