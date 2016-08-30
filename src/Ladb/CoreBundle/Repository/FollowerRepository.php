<?php

namespace Ladb\CoreBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class FollowerRepository extends AbstractEntityRepository {

	/////

	public function findOneByFollowingUserIdAndUser($followingUserId, User $user) {
		$query = $this->getEntityManager()
			->createQuery('
                SELECT f FROM LadbCoreBundle:Follower f
                WHERE f.followingUserId = :followingUserId AND f.user = :user
            ')
			->setParameter('followingUserId', $followingUserId)
			->setParameter('user', $user);

		try {
			return $query->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findByFollowingUser(User $followingUser) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->where('f.followingUser = :followingUser')
			->setParameter('followingUser', $followingUser);

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	//////

	public function existsByFollowingUserIdAndUser($followingUserId, User $user) {
		$query = $this->getEntityManager()
			->createQuery('
                SELECT count(f.id) FROM LadbCoreBundle:Follower f
                WHERE f.followingUserId = :followingUserId AND f.user = :user
            ')
			->setParameter('followingUserId', $followingUserId)
			->setParameter('user', $user);

		try {
			return $query->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return false;
		}
	}

	//////

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('contributors-all' == $filter) {
			$queryBuilder
				->addOrderBy('u.contributionCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-creations' == $filter) {
			$queryBuilder
				->addOrderBy('u.publishedCreationCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-plans' == $filter) {
			$queryBuilder
				->addOrderBy('u.publishedPlanCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-howtos' == $filter) {
			$queryBuilder
				->addOrderBy('u.publishedHowtoCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-workshops' == $filter) {
			$queryBuilder
				->addOrderBy('u.publishedWorkshopCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-comments' == $filter) {
			$queryBuilder
				->addOrderBy('u.commentCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-finds' == $filter) {
			$queryBuilder
				->addOrderBy('u.publishedFindCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('popular-followers' == $filter) {
			$queryBuilder
				->addOrderBy('u.followerCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('u.recievedLikeCount', 'DESC')
			;
		} else {
			$queryBuilder
				->addOrderBy('u.createdAt', 'DESC')
			;
		}
	}

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.followingUser', 'u')
			->where('f.user = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByFollowingUser(User $followingUser, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->where('f.followingUser = :followingUser')
			->setParameter('followingUser', $followingUser)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}