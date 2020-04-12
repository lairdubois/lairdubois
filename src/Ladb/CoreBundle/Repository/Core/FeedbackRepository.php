<?php

namespace Ladb\CoreBundle\Repository\Core;

use Doctrine\ORM\NonUniqueResultException;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;
use Ladb\CoreBundle\Utils\TypableUtils;

class FeedbackRepository extends AbstractEntityRepository {

	/////

	public function existsByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(f.id)' ))
			->from($this->getEntityName(), 'f')
			->where('f.entityType = :entityType')
			->andWhere('f.entityId = :entityId')
			->andWhere('f.user = :user')
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
			->select(array( 'f', 'u' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->where('f.entityType = :entityType')
			->andWhere('f.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->orderBy('f.createdAt', 'ASC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPaginedByUserGroupByEntityType(User $user, $offset, $limit) {

		// Retrieve concat feedback ids per entity
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'MAX(f.createdAt) AS mx', 'f.entityType', 'f.entityId', 'count(f.id)', 'GROUP_CONCAT(f.id)' ))
			->from($this->getEntityName(), 'f')
			->where('f.user = :user')
			->groupBy('f.entityType, f.entityId')
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

			// Retrieve feedbacks
			$feedbackCount = $concatResult[1];
			if ($feedbackCount == 1) {
				$feedbacks = array( $concatResult[0] );
			} else {
				$feedbackIds = explode(',', $concatResult[2]);
				$feedbacks = $this->findByIds($feedbackIds, 'f.createdAt', 'ASC');
			}

			$items[] = array(
				'entity'    => $entity,
				'feedbacks' => $feedbacks,
			);

		}

		return $items;
	}

	/////

	/*
	 * [
	 * 	[ 'entity' => ENTITY, 'feedbacks' => FEEDBACKS ],
	 *  ...,
	 * ]
	 */

	public function findByIds(array $ids, $orderBySort = null, $orderByOrder = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u', 'ua' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->leftJoin('u.avatar', 'ua')
			->where($queryBuilder->expr()->in('f.id', $ids))
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