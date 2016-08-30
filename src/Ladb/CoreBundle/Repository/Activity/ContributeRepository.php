<?php

namespace Ladb\CoreBundle\Repository\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class ContributeRepository extends AbstractEntityRepository {

	/////

	public function findByValue(\Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue $value) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.value = :value')
			->setParameter('value', $value)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->innerJoin('a.value', 'v')
			->andWhere('v.parentEntityType = :entityType')
			->andWhere('v.parentEntityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->orderBy('a.createdAt', 'ASC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}