<?php

namespace App\Repository\Knowledge\Value;

use App\Repository\AbstractEntityRepository;

class BaseValueRepository extends AbstractEntityRepository {

	/////

	/*
	 * [
	 * 	ID1, ID2, ...
	 * ]
	 */
	public function findUserIdsByParentEntityTypeAndParentEntityId($parentEntityType, $parentEntityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select('u.id')
			->from($this->getEntityName(), 'v')
			->innerJoin('v.user', 'u')
			->where('v.parentEntityType = :parentEntityType')
			->andWhere('v.parentEntityId = :parentEntityId')
			->setParameter('parentEntityType', $parentEntityType)
			->setParameter('parentEntityId', $parentEntityId)
			->groupBy('v.user')
		;

		try {
			$result = $queryBuilder->getQuery()->getResult();
			$userIds = array();
			foreach ($result as $userId) {
				$userIds[] = $userId['id'];
			}
			return $userIds;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}