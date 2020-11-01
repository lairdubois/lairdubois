<?php

namespace Ladb\CoreBundle\Repository\Opencutlist;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Opencutlist\Access;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class AccessRepository extends AbstractEntityRepository {

	public function countUniqueGroupByDay($kind = null, $env = null, $backwardDays = 28, $continentCode = null, $language = null) {
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
			if (!is_null($continentCode)) {
				$sql .= 'AND continent_code = ? ';
			}
			if (!is_null($language)) {
				$sql .= 'AND client_ocl_language = ? ';
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
		$valueIndex = 1;
		if (!is_null($kind)) {
			$valueIndex += 1;
			$stmt->bindValue($valueIndex, Access::validKind($kind));
		}
		if (!is_null($env)) {
			$valueIndex += 1;
			$stmt->bindValue($valueIndex, Access::validEnv($env));
		}
		if (!is_null($continentCode)) {
			$valueIndex += 1;
			$stmt->bindValue($valueIndex, $continentCode);
		}
		if (!is_null($language)) {
			$valueIndex += 1;
			$stmt->bindValue($valueIndex, $language);
		}
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function countUniqueGroupByCountryCode($kind = null, $env = null, $backwardDays = 28, $continentCode = null, $language = null) {
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
			if (!is_null($continentCode)) {
				$sql .= 'AND continent_code = ? ';
			}
			if (!is_null($language)) {
				$sql .= 'AND client_ocl_language = ? ';
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
		$valueIndex = 1;
		if (!is_null($kind)) {
			$valueIndex += 1;
			$stmt->bindValue($valueIndex, Access::validKind($kind));
		}
		if (!is_null($env)) {
			$valueIndex += 1;
			$stmt->bindValue($valueIndex, Access::validEnv($env));
		}
		if (!is_null($continentCode)) {
			$valueIndex += 1;
			$stmt->bindValue($valueIndex, $continentCode);
		}
		if (!is_null($language)) {
			$valueIndex += 1;
			$stmt->bindValue($valueIndex, $language);
		}
		$stmt->execute();

		return $stmt->fetchAll();
	}

	/////

	public function findPagined($offset, $limit, $env = null, $backwardDays = 28, $continentCode = null, $language = null) {
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
			$queryBuilder->setParameter('env', Access::validEnv($env));
		}

		if (!is_null($continentCode)) {
			$queryBuilder->andWhere('a.continentCode = :continentCode');
			$queryBuilder->setParameter('continentCode', $continentCode);
		}

		if (!is_null($language)) {
			$queryBuilder->andWhere('a.clientOclLanguage = :language');
			$queryBuilder->setParameter('language', $language);
		}

		return new Paginator($queryBuilder->getQuery());
	}

}