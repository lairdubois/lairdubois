<?php

namespace Ladb\CoreBundle\Repository\Core;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\VotableParentInterface;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;
use Ladb\CoreBundle\Utils\TypableUtils;

class VoteRepository extends AbstractEntityRepository {

	/////

	public function findOneByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'v' ))
			->from($this->getEntityName(), 'v')
			->where('v.entityType = :entityType')
			->andWhere('v.entityId = :entityId')
			->andWhere('v.user = :user')
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

	public function existsByEntityTypeAndEntityIdAndUser($entityType, $entityId, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select('count(v.id)')
			->from($this->getEntityName(), 'v')
			->where('v.entityType = :entityType')
			->andWhere('v.entityId = :entityId')
			->andWhere('v.user = :user')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return false;
		}
	}

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'v' ))
			->from($this->getEntityName(), 'v')
			->where('v.entityType = :entityType')
			->andWhere('v.entityId = :entityId')
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

	/*
	 * [
	 * 	[ 'user' => USER, 'votables' => VOTABLES ],
	 *  ...,
	 * ]
	 */
	public function findPaginedByVotableParent(VotableParentInterface $votableParent, $offset, $limit, $filter = 'positive') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'v', 'u', 'MAX(v.id) AS mx', 'COUNT(v.id)', 'GROUP_CONCAT(v.entityType ORDER BY v.id ASC)', 'GROUP_CONCAT(v.entityId ORDER BY v.id ASC)' ))
			->from($this->getEntityName(), 'v')
			->innerJoin('v.user', 'u')
			->orderBy('mx', 'DESC')
			->where('v.parentEntityType = :parentEntityType')
			->andWhere('v.parentEntityId = :parentEntityId')
			->groupBy('v.user')
			->setParameter('parentEntityType', $votableParent->getType())
			->setParameter('parentEntityId', $votableParent->getId())
			->setFirstResult($offset)
			->setMaxResults($limit)
		;
		if ($filter == 'negative') {
			$queryBuilder
				->andWhere('v.score < 0')
			;
		} else {
			$queryBuilder
				->andWhere('v.score > 0')
			;
		}
		try {
			$concatResults = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
		}

		$items = array();

		foreach ($concatResults as $concatResult) {

			$vote = $concatResult[0];
			$voteCount = $concatResult[1];
			$entityTypes = explode(',', $concatResult[2]);
			$entityIds = explode(',', $concatResult[3]);

			$votables = array();

			for ($i = 0 ; $i < $voteCount; ++$i) {

				// Retrive related entity
				$entityClassName = TypableUtils::getClassByType($entityTypes[$i]);
				if (is_null($entityClassName)) {
					continue;
				}
				$entityRepository = $this->getEntityManager()->getRepository($entityClassName);
				$votable = $entityRepository->findOneByIdJoinedOn($entityIds[$i], $entityRepository->getDefaultJoinOptions());
				if (is_null($votable)) {
					continue;
				}
				if ($votable instanceof ViewableInterface && !$votable->getIsViewable()) {
					continue;
				}

				$votables[] = $votable;
			}

			$items[] = array(
				'user'     => $vote->getUser(),
				'votables' => $votables,
			);

		}

		return $items;
	}

}