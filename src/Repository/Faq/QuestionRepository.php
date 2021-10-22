<?php

namespace App\Repository\Faq;

use Doctrine\ORM\Tools\Pagination\Paginator;
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
			->select(array( 'q', 'u', 'uav', 'bbs', 'tgs' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->innerJoin('u.avatar', 'uav')
			->leftJoin('q.bodyBlocks', 'bbs')
			->leftJoin('q.tags', 'tgs')
			->where('q.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneBySlugJoinedOnAll($slug) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'q', 'u', 'tgs' ))
			->from($this->getEntityName(), 'q')
			->innerJoin('q.user', 'u')
			->leftJoin('q.tags', 'tgs')
			->where('q.slug = :slug')
			->setParameter('slug', $slug)
		;

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
			->addOrderBy('q.weight', 'DESC')
		;
	}

	public function findPagined($offset, $limit, $filter = 'weight', $includeDrafts = false) {
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

}