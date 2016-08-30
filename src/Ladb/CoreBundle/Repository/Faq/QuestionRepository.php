<?php

namespace Ladb\CoreBundle\Repository\Faq;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class QuestionRepository extends AbstractEntityRepository {

	public function createIsNotDraftQueryBuilder() {
		return $this->createQueryBuilder('a')->where('a.isDraft = false');	// FOSElasticaBundle bug -> use 'a'
	}

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