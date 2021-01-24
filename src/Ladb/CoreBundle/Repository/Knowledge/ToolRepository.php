<?php

namespace Ladb\CoreBundle\Repository\Knowledge;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue;
use Ladb\CoreBundle\Entity\Knowledge\Tool;

class ToolRepository extends AbstractKnowledgeRepository {

	/////

	public function findUserIdsById($id) {
		return $this->getEntityManager()->getRepository(BaseValue::CLASS_NAME)->findUserIdsByParentEntityTypeAndParentEntityId(Tool::TYPE, $id);
	}

	//////

	public function existsByIdentity($identity, $excludedId = 0) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(b.id)' ))
			->from($this->getEntityName(), 'b')
			->where('LOWER(b.identity) = :identity')
			->setParameter('identity', $identity)
		;

		if ($excludedId > 0) {
			$queryBuilder
				->andWhere('b.id != :excludedId')
				->setParameter('excludedId', $excludedId)
			;
		}

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return false;
		}
	}

	/////

	public function findOneByIdJoinedOnOptimized($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'b', 'mp', 'covv' ))
			->from($this->getEntityName(), 'b')
			->leftJoin('b.mainPicture', 'mp')
			->leftJoin('b.photoValues', 'phv')
			->where('b.id = :id')
			->setParameter('id', $id)
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
			->select(array( 'b', 'mp' ))
			->from($this->getEntityName(), 'b')
			->leftJoin('b.mainPicture', 'mp')
			->where($queryBuilder->expr()->in('b.id', $ids))
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPagined($offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'b', 'mp' ))
			->from($this->getEntityName(), 'b')
			->innerJoin('b.mainPicture', 'mp')
			->where('b.isDraft = false')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('popular-views' == $filter) {
			$queryBuilder
				->addOrderBy('b.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('b.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('b.commentCount', 'DESC')
			;
		} else if ('collaborative-contributors' == $filter) {
			$queryBuilder
				->addOrderBy('b.contributorCount', 'DESC')
			;
		} else if ('order-alphabetical' == $filter) {
			$queryBuilder
				->addOrderBy('b.title', 'ASC')
			;
		} else if ('order-density' == $filter) {
			$queryBuilder
				->addOrderBy('b.density', 'DESC')
			;
		}
		$queryBuilder
			->addOrderBy('b.changedAt', 'DESC')
		;
	}

}