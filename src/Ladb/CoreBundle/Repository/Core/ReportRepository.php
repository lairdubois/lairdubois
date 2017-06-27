<?php

namespace Ladb\CoreBundle\Repository\Core;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class ReportRepository extends AbstractEntityRepository {

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from($this->getEntityName(), 'r')
			->where('r.entityType = :entityType')
			->andWhere('r.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}