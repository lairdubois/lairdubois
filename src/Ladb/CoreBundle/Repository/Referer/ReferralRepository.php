<?php

namespace Ladb\CoreBundle\Repository\Referer;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class ReferralRepository extends AbstractEntityRepository {

	/////

	public function findOneByEntityTypeAndEntityIdAndUrl($entityType, $entityId, $url) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from($this->getEntityName(), 'r')
			->where('r.entityType = :entityType')
			->andWhere('r.entityId = :entityId')
			->andWhere('r.url = :url')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setParameter('url', $url)
			->setMaxResults(1)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}

	}

}