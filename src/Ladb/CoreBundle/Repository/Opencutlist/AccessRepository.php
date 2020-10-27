<?php

namespace Ladb\CoreBundle\Repository\Opencutlist;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class AccessRepository extends AbstractEntityRepository {

	public function countUniqueGroupByDay($kind = null, $env = null, $backwardDays = 28) {
		$sql = 	'SELECT count(id) AS count, day ';
		$sql .= 'FROM (';
		$sql .= 	'SELECT id, DATE_FORMAT(created_at, "%Y-%m-%d") AS day, client_ip4 FROM tbl_opencutlist_access ';
		$sql .= 	'WHERE created_at > ? ';
		if (!is_null($kind)) {
			$sql .= 'AND kind = ? ';
		}
		if (!is_null($env)) {
			$sql .= 'AND env = ? ';
		}
		$sql .= 	'AND analyzed = 1 AND (client_sketchup_version IS NOT NULL OR client_ocl_version IS NOT NULL) ';
		$sql .= 	'GROUP BY day, client_ip4';
		$sql .= ') t0 ';
		$sql .= 'GROUP BY day ';
		$sql .= 'ORDER BY day ASC';

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

	public function countUniqueGroupByCountryCode($kind = null, $env = null, $backwardDays = 28) {
		$sql = 	'SELECT count(*) as count, country_code as countryCode ';
		$sql .= 'FROM (';
		$sql .= 	'SELECT * FROM tbl_opencutlist_access ';
		$sql .= 	'WHERE created_at > ? ';
			if (!is_null($kind)) {
				$sql .= 'AND kind = ? ';
			}
			if (!is_null($env)) {
				$sql .= 'AND env = ? ';
			}
		$sql .= 	'AND analyzed = 1 AND country_code IS NOT NULL AND (client_sketchup_version IS NOT NULL OR client_ocl_version IS NOT NULL) ';
		$sql .= 	'GROUP BY client_ip4';
		$sql .= ') t0 ';
		$sql .= 'GROUP BY countryCode ';
		$sql .= 'ORDER BY count DESC, country_code ASC';

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