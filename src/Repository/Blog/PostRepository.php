<?php

namespace App\Repository\Blog;

use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Blog\Post;
use App\Repository\AbstractEntityRepository;

class PostRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ), array( 'inner', 'mainPicture', 'mp' ) );
	}

	/////

	public function findOneByIdJoinedOnUser($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->where('p.id = :id')
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
			->select(array( 'p', 'u', 'uav', 'mp', 'bbs', 'tgs' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('u.avatar', 'uav')
			->innerJoin('p.mainPicture', 'mp')
			->leftJoin('p.bodyBlocks', 'bbs')
			->leftJoin('p.tags', 'tgs')
			->where('p.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findOneLastOnHighlightLevel($highlightLevel = Post::HIGHLIGHT_LEVEL_ALL) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->where('p.isDraft = 0')
			->andWhere('p.highlightLevel >= :highlightLevel')
			->orderBy('p.changedAt', 'DESC')
			->setParameter('highlightLevel', $highlightLevel)
			->setMaxResults(1)
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
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->where($queryBuilder->expr()->in('p.id', $ids))
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
				->addOrderBy('p.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('p.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('p.commentCount', 'DESC')
			;
		}
		$queryBuilder
			->addOrderBy('p.changedAt', 'DESC')
		;
	}

	public function findPagined($offset, $limit, $filter = 'recent', $includeDrafts = false) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		if ('draft' == $filter && $includeDrafts) {
			$queryBuilder
				->andWhere('p.isDraft = true')
			;
		} else if (!$includeDrafts) {
			$queryBuilder
				->andWhere('p.isDraft = false')
			;
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}
	
}