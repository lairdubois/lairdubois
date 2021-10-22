<?php

namespace App\Repository\Core;

use Doctrine\ORM\NonUniqueResultException;
use App\Entity\Core\User;
use App\Model\HiddableInterface;
use App\Repository\AbstractEntityRepository;
use App\Utils\TypableUtils;

class ReviewRepository extends AbstractEntityRepository {

	/////

	public function existsByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(r.id)' ))
			->from($this->getEntityName(), 'r')
			->where('r.entityType = :entityType')
			->andWhere('r.entityId = :entityId')
			->andWhere('r.user = :user')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (NonUniqueResultException $e) {
			return false;
		}
	}

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r', 'u' ))
			->from($this->getEntityName(), 'r')
			->innerJoin('r.user', 'u')
			->where('r.entityType = :entityType')
			->andWhere('r.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->orderBy('r.createdAt', 'DESC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPaginedByUserGroupByEntityType(User $user, $offset, $limit) {

		// Retrieve concat comment ids per entity
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r', 'MAX(r.createdAt) AS mx', 'r.entityType', 'r.entityId', 'count(r.id)', 'GROUP_CONCAT(r.id)' ))
			->from($this->getEntityName(), 'r')
			->where('r.user = :user')
			->groupBy('r.entityType, r.entityId')
			->orderBy('mx', 'DESC')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;
		try {
			$concatResults = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
		}

		$items = array();

		foreach ($concatResults as $concatResult) {

			$entityType = $concatResult['entityType'];
			$entityId = $concatResult['entityId'];

			// Retrive related entity
			$entityClassName = TypableUtils::getClassByType($entityType);
			if (is_null($entityClassName)) {
				continue;
			}
			$entityRepository = $this->getEntityManager()->getRepository($entityClassName);
			$entity = $entityRepository->findOneByIdJoinedOn($entityId, $entityRepository->getDefaultJoinOptions());
			if (is_null($entity)) {
				continue;
			}
			if ($entity instanceof HiddableInterface && !$entity->getIsPublic()) {
				continue;
			}

			// Retrieve reviews
			$reviewCount = $concatResult[1];
			if ($reviewCount == 1) {
				$reviews = array( $concatResult[0] );
			} else {
				$reviewIds = explode(',', $concatResult[2]);
				$reviews = $this->findByIds($reviewIds, 'r.createdAt', 'ASC');
			}

			$items[] = array(
				'entity'  => $entity,
				'reviews' => $reviews,
			);

		}

		return $items;
	}

	/////

	/*
	 * [
	 * 	[ 'entity' => ENTITY, 'reviews' => COMMENTS ],
	 *  ...,
	 * ]
	 */

	public function findByIds(array $ids, $orderBySort = null, $orderByOrder = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r', 'u', 'ua' ))
			->from($this->getEntityName(), 'r')
			->innerJoin('r.user', 'u')
			->leftJoin('u.avatar', 'ua')
			->where($queryBuilder->expr()->in('r.id', $ids))
		;

		if (null !== $orderBySort) {
			$queryBuilder
				->addOrderBy($orderBySort, $orderByOrder);
		}

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}