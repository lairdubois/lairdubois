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

	public function countUniqueGroupByCountryCode($kind = null, $env = null, $backwardDays = 28) {
		$sql = 	'SELECT count(*) as count, country_code as countryCode ';
		$sql .= 'FROM (';
		$sql .= 	'SELECT * FROM tbl_opencutlist_access GROUP BY client_ip4';
		$sql .= ') t0 ';
		$sql .= 'WHERE created_at > ? ';
		if (!is_null($kind)) {
			$sql .= 'AND kind = ? ';
		}
		if (!is_null($env)) {
			$sql .= 'AND env = ? ';
		}
		$sql .= 'GROUP BY countryCode ';
		$sql .= 'ORDER BY count DESC';

		$startAt = (new \DateTime())->sub(new \DateInterval('P'.$backwardDays.'D'));

		$conn = $this->getEntityManager()->getConnection();
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, $startAt->format('Y-m-d H:i:s'));
		if (!is_null($kind)) {
			$stmt->bindValue(2, $kind);
		}
		if (!is_null($env)) {
			$stmt->bindValue(is_null($kind) ? 2 : 3, $env);
		}
		$stmt->execute();

		return $stmt->fetchAll();
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