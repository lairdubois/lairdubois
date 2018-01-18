<?php

namespace Ladb\CoreBundle\Repository\Core;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class FollowerRepository extends AbstractEntityRepository {

	/////

	public function findOneByFollowingUserIdAndUser($followingUserId, User $user) {
		$query = $this->getEntityManager()
			->createQuery('
                SELECT f FROM LadbCoreBundle:Core\Follower f
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
                SELECT count(f.id) FROM LadbCoreBundle:Core\Follower f
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

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u', 'm' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.followingUser', 'u')
			->innerJoin('u.meta', 'm')
			->where('f.user = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('contributors-all' == $filter) {
			$queryBuilder
				->addOrderBy('m.contributionCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-creations' == $filter) {
			$queryBuilder
				->addOrderBy('m.publicCreationCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-plans' == $filter) {
			$queryBuilder
				->addOrderBy('m.publicPlanCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-howtos' == $filter) {
			$queryBuilder
				->addOrderBy('m.publicHowtoCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-workshops' == $filter) {
			$queryBuilder
				->addOrderBy('m.publicWorkshopCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-comments' == $filter) {
			$queryBuilder
				->addOrderBy('m.commentCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('contributors-finds' == $filter) {
			$queryBuilder
				->addOrderBy('m.publicFindCount', 'DESC')
				->addOrderBy('u.createdAt', 'DESC')
			;
		} else if ('popular-followers' == $filter) {
			$queryBuilder
				->addOrderBy('m.followerCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('m.recievedLikeCount', 'DESC')
			;
		} else {
			$queryBuilder
				->addOrderBy('u.createdAt', 'DESC')
			;
		}
	}

	public function findPaginedByFollowingUser(User $followingUser, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u', 'm' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->innerJoin('u.meta', 'm')
			->where('f.followingUser = :followingUser')
			->setParameter('followingUser', $followingUser)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}