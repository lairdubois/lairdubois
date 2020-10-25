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
			->where('a.analyzed = true')
			->andWhere('a.clientSketchupVersion IS NOT NULL')
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
			$queryBuilder->andWhere('a.env = :env');
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
			->where('a.analyzed = true')
			->andWhere('a.clientSketchupVersion IS NOT NULL')
			->groupBy('a.countryCode')
			->orderBy('count', 'DESC')
		;

		if (!is_null($kind)) {
			$queryBuilder->where('a.kind = :kind');
			$queryBuilder->setParameter('kind', $kind);
		}
		if (!is_null($env)) {
			$queryBuilder->andWhere('a.env = :env');
			$queryBuilder->setParameter('env', $env);
		}

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPagined($offset, $limit, $env = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->setFirstResult($offset)
			->setMaxResults($limit)
			->addOrderBy('a.createdAt', 'DESC')
		;

		if (!is_null($env)) {
			$queryBuilder->andWhere('a.env = :env');
			$queryBuilder->setParameter('env', $env);
		}

		return new Paginator($queryBuilder->getQuery());
	}

}