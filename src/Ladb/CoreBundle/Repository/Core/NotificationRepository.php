<?php

namespace Ladb\CoreBundle\Repository\Core;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class NotificationRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'activity', 'a' ) );
	}

	/////

	public function countUnlistedByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(n.id)' ))
			->from($this->getEntityName(), 'n')
			->where('n.user = :user')
			->andWhere('n.isListed = 0')
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findOneByIdJoinedOnActivity($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'n', 'a' ))
			->from($this->getEntityName(), 'n')
			->innerJoin('n.activity', 'a')
			->where('n.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByPendingEmailAndActivityInstanceOf($activityInstanceOf) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'n', 'a', 'u' ))
			->from($this->getEntityName(), 'n')
			->innerJoin('n.user', 'u')
			->innerJoin('n.activity', 'a')
			->where('n.isPendingEmail = 1')
			->andWhere('n.isListed = 0')
			->andWhere('a INSTANCE OF '.$activityInstanceOf)
			->orderBy('u.id', 'ASC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}

	}

	/////

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'n', 'a', 'au' ))
			->from($this->getEntityName(), 'n')
			->innerJoin('n.activity', 'a')
			->innerJoin('a.user', 'au')
			->where('n.user = :user')
			->setParameter('user', $user)
			->orderBy('a.createdAt', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('activity-like' == $filter) {
			$queryBuilder
				->andWhere('a INSTANCE OF \\Ladb\\CoreBundle\\Entity\\Core\\Activity\\Like')
			;
		} elseif ('activity-comment' == $filter) {
			$queryBuilder
				->andWhere('a INSTANCE OF \\Ladb\\CoreBundle\\Entity\\Core\\Activity\\Comment')
			;
		} elseif ('activity-follow' == $filter) {
			$queryBuilder
				->andWhere('a INSTANCE OF \\Ladb\\CoreBundle\\Entity\\Core\\Activity\\Follow')
			;
		} elseif ('activity-publish' == $filter) {
			$queryBuilder
				->andWhere('a INSTANCE OF \\Ladb\\CoreBundle\\Entity\\Core\\Activity\\Publish')
			;
		} elseif ('activity-vote' == $filter) {
			$queryBuilder
				->andWhere('a INSTANCE OF \\Ladb\\CoreBundle\\Entity\\Core\\Activity\\Vote')
			;
		} elseif ('activity-join' == $filter) {
			$queryBuilder
				->andWhere('a INSTANCE OF \\Ladb\\CoreBundle\\Entity\\Core\\Activity\\Join')
			;
		} elseif ('activity-answer' == $filter) {
			$queryBuilder
				->andWhere('a INSTANCE OF \\Ladb\\CoreBundle\\Entity\\Core\\Activity\\Answer')
			;
		}
		$queryBuilder
			->addOrderBy('a.createdAt', 'DESC')
		;
	}

}