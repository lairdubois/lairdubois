<?php

namespace App\Repository\Core;

use App\Repository\AbstractEntityRepository;

class MentionRepository extends AbstractEntityRepository {

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'm' ))
			->from($this->getEntityName(), 'm')
			->where('m.entityType = :entityType')
			->andWhere('m.entityId = :entityId')
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