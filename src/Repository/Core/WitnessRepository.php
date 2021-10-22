<?php

namespace App\Repository\Core;

use App\Entity\Core\User;
use App\Repository\AbstractEntityRepository;

class WitnessRepository extends AbstractEntityRepository {

	/////

	public function findOneByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'w' ))
			->from($this->getEntityName(), 'w')
			->where('w.entityType = :entityType')
			->andWhere('w.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}