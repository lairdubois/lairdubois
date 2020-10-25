<?php

namespace Ladb\CoreBundle\Repository\Opencutlist;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class AccessRepository extends AbstractEntityRepository {

	public function countGroupByDay($kind = null, $env = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(a.id) as count, YEAR(a.createdAt) as year, MONTH(a.createdAt) as month, DAY(a.createdAt) as day' ))
			->from($this->getEntityName(), 'a')
			->groupBy('year')
			->addGroupBy('month')
			->addGroupBy('day')
			->orderBy('a.createdAt', 'DESC')
		;

		if (!is_null($kind)) {
			$queryBuilder->where('a.kind = :kind');
			$queryBuilder->setParameter('kind', $kind);
		}
		if (!is_null($env)) {
			if (!is_null($kind)) {
				$queryBuilder->andWhere('a.env = :env');
			} else {
				$queryBuilder->andWhere('a.env = :env');
			}
			$queryBuilder->setParameter('env', $env);
		}

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function countGroupByCountryCode($kind = null, $env = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(a.id) as count, a.countryCode' ))
			->from($this->getEntityName(), 'a')
			->addGroupBy('a.countryCode')
			->orderBy('count', 'DESC')
		;

		if (!is_null($kind)) {
			$queryBuilder->where('a.kind = :kind');
			$queryBuilder->setParameter('kind', $kind);
		}
		if (!is_null($env)) {
			if (!is_null($kind)) {
				$queryBuilder->andWhere('a.env = :env');
			} else {
				$queryBuilder->andWhere('a.env = :env');
			}
			$queryBuilder->setParameter('env', $env);
		}

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPagined($offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		$queryBuilder
			->addOrderBy('a.createdAt', 'DESC');
	}

}