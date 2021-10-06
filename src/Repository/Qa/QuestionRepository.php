<?php

namespace App\Repository\Qa;

use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Core\User;
use App\Entity\Howto\Howto;
use App\Entity\Wonder\Creation;
use App\Entity\Wonder\Plan;
use App\Repository\AbstractEntityRepository;

class QuestionRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ) );
	}

	/////

	public function findOneByIdJoinedOnUser($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->where('q.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneByIdJoinedOnOptimized($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u', 'bbs' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->innerJoin('q.bodyBlocks', 'bbs')
			->where('q.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneFirstByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->where('q.isDraft = false')
			->andWhere('q.user = :user')
			->orderBy('q.id', 'ASC')
			->setParameter('user', $user)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneLastByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->where('q.isDraft = false')
			->andWhere('q.user = :user')
			->orderBy('q.id', 'DESC')
			->setParameter('user', $user)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOnePreviousByUserAndId(User $user, $id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->where('q.isDraft = false')
			->andWhere('q.user = :user')
			->andWhere('q.id < :id')
			->orderBy('q.id', 'DESC')
			->setParameter('user', $user)
			->setParameter('id', $id)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneNextByUserAndId(User $user, $id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->where('q.isDraft = false')
			->andWhere('q.user = :user')
			->andWhere('q.id > :id')
			->orderBy('q.id', 'ASC')
			->setParameter('user', $user)
			->setParameter('id', $id)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByIds(array $ids) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->where($queryBuilder->expr()->in('q.id', $ids))
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('popular-views' == $filter) {
			$queryBuilder
				->addOrderBy('q.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('q.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('q.commentCount', 'DESC')
			;
		}
		$queryBuilder
			->addOrderBy('q.changedAt', 'DESC')
		;
	}

	public function findPagined($offset, $limit, $filter = 'recent', $includeDrafts = false) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		if ('draft' == $filter && $includeDrafts) {
			$queryBuilder
				->andWhere('q.isDraft = true')
			;
		} else if (!$includeDrafts) {
			$queryBuilder
				->andWhere('q.isDraft = false')
			;
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent', $includeDrafts = false) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->where('u = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		if ('draft' == $filter && $includeDrafts) {
			$queryBuilder
				->andWhere('q.isDraft = true')
			;
		} else if (!$includeDrafts) {
			$queryBuilder
				->andWhere('q.isDraft = false')
			;
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByCreation(Creation $creation, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u', 'c' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->innerJoin('q.creations', 'c')
			->where('q.isDraft = false')
			->andWhere('c = :creation')
			->setParameter('creation', $creation)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByPlan(Plan $plan, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u', 'p' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->innerJoin('q.plans', 'p')
			->where('q.isDraft = false')
			->andWhere('p = :plan')
			->setParameter('plan', $plan)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByHowto(Howto $howto, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u', 'h' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->innerJoin('q.howtos', 'h')
			->where('q.isDraft = false')
			->andWhere('h = :howto')
			->setParameter('howto', $howto)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}