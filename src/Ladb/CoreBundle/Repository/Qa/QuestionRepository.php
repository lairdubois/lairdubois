<?php

namespace Ladb\CoreBundle\Repository\Qa;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Blog\Question;
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
			->select(array( 'p', 'u', 'bbs' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.bodyBlocks', 'bbs')
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
			->select(array( 'p', 'u' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
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