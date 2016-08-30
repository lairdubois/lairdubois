<?php

namespace Ladb\CoreBundle\Repository\Referer;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class RefererRepository extends AbstractEntityRepository {

	/////

	public function findOneByBaseUrl($baseUrl) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from($this->getEntityName(), 'r')
			->where('r.baseUrl = :baseUrl')
			->setParameter('baseUrl', $baseUrl)
			->setMaxResults(1)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}

	}

}