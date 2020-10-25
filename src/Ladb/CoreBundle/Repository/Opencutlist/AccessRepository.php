<?php

namespace Ladb\CoreBundle\Repository\Opencutlist;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class AccessRepository extends AbstractEntityRepository {

	public function countGroupByDay($kind = null, $env = null, $backwardDays = 28) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(a.id) as count, YEAR(a.createdAt) as year, MONTH(a.createdAt) as month, DAY(a.createdAt) as day' ))
			->from($this->getEntityName(), 'a')
			->where('a.analyzed = true')
			->andWhere('a.clientSketchupVersion IS NOT NULL OR a.clientOclVersion IS NOT NULL')
			->groupBy('year')
			->addGroupBy('month')
			->addGroupBy('day')
			->orderBy('a.createdAt', 'DESC')
		;

		$startAt = (new \DateTime())->sub(new \DateInterval('P'.$backwardDays.'D'));
		$queryBuilder
			->where('a.createdAt >= :startAt')
			->setParameter('startAt', $startAt)
		;

		if (!is_null($kind)) {
			$queryBuilder->andWhere('a.kind = :kind');
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

	public function countGroupByCountryCode($kind = null, $env = null, $backwardDays = 28) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(a.id) as count, a.countryCode' ))
			->from($this->getEntityName(), 'a')
			->where('a.analyzed = true')
			->andWhere('a.clientSketchupVersion IS NOT NULL OR a.clientOclVersion IS NOT NULL')
			->groupBy('a.countryCode')
			->orderBy('count', 'DESC')
		;

		$startAt = (new \DateTime())->sub(new \DateInterval('P'.$backwardDays.'D'));
		$queryBuilder
			->where('a.createdAt >= :startAt')
			->setParameter('startAt', $startAt)
		;

		if (!is_null($kind)) {
			$queryBuilder->andWhere('a.kind = :kind');
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

	public function findPagined($offset, $limit, $env = null, $backwardDays = 28) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->setFirstResult($offset)
			->setMaxResults($limit)
			->addOrderBy('a.createdAt', 'DESC')
		;

		$startAt = (new \DateTime())->sub(new \DateInterval('P'.$backwardDays.'D'));
		$queryBuilder
			->where('a.createdAt >= :startAt')
			->setParameter('startAt', $startAt)
		;

		if (!is_null($env)) {
			$queryBuilder->andWhere('a.env = :env');
			$queryBuilder->setParameter('env', $env);
		}

		return new Paginator($queryBuilder->getQuery());
	}

}