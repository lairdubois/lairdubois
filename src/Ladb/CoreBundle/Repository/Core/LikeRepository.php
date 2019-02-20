<?php

namespace Ladb\CoreBundle\Repository\Core;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;
use Ladb\CoreBundle\Utils\TypableUtils;

class LikeRepository extends AbstractEntityRepository {

	/////

	public function existsByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(l.id)' ))
			->from($this->getEntityName(), 'l')
			->where('l.entityType = :entityType')
			->andWhere('l.entityId = :entityId')
			->andWhere('l.user = :user')
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

	public function findOneByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'l' ))
			->from($this->getEntityName(), 'l')
			->where('l.entityType = :entityType')
			->andWhere('l.entityId = :entityId')
			->andWhere('l.user = :user')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'l' ))
			->from($this->getEntityName(), 'l')
			->where('l.entityType = :entityType')
			->andWhere('l.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPaginedByEntityTypeAndEntityIdJoinedOnUser($entityType, $entityId, $offset, $limit) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'l', 'u' ))
			->from($this->getEntityName(), 'l')
			->innerJoin('l.user', 'u')
			->where('l.entityType = :entityType')
			->andWhere('l.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		return new Paginator($queryBuilder->getQuery());
	}

	/////

	/*
	 * [
	 * 	[ 'user' => USER, 'likables' => LIKABLES ],
	 *  ...,
	 * ]
	 */
	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'sent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'l', 'eu', 'MAX(l.id) AS mx', 'COUNT(l.id)', 'GROUP_CONCAT(l.entityType ORDER BY l.id ASC)', 'GROUP_CONCAT(l.entityId ORDER BY l.id ASC)' ))
			->from($this->getEntityName(), 'l')
			->leftJoin('l.entityUser', 'eu')
			->orderBy('mx', 'DESC')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;
		if ($filter == 'recieved') {
			$queryBuilder
				->where('l.entityUser = :user')
				->groupBy('l.user')
			;
		} else {
			$queryBuilder
				->where('l.user = :user')
				->groupBy('eu')
			;
		}
		try {
			$concatResults = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
		}

		$items = array();
		$recievedFilter = $filter == 'recieved';

		foreach ($concatResults as $concatResult) {

			$like = $concatResult[0];
			$likeCount = $concatResult[1];
			$entityTypes = explode(',', $concatResult[2]);
			$entityIds = explode(',', $concatResult[3]);

			$likables = array();

			for ($i = 0 ; $i < $likeCount; ++$i) {

				// Retrive related entity
				$entityClassName = TypableUtils::getClassByType($entityTypes[$i]);
				if (is_null($entityClassName)) {
					continue;
				}
				$entityRepository = $this->getEntityManager()->getRepository($entityClassName);
				$likable = $entityRepository->findOneByIdJoinedOn($entityIds[$i], $entityRepository->getDefaultJoinOptions());
				if (is_null($likable)) {
					continue;
				}
				if ($likable instanceof HiddableInterface && !$likable->getIsPublic()) {
					continue;
				}

				$likables[] = $likable;
			}

			$items[] = array(
				'user'     => $recievedFilter ? $like->getUser() : $like->getEntityUser(),
				'likables' => $likables,
			);

		}

		return $items;
	}

}