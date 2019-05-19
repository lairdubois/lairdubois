<?php

namespace Ladb\CoreBundle\Repository\Knowledge;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue;
use Ladb\CoreBundle\Entity\Knowledge\Software;

class SoftwareRepository extends AbstractKnowledgeRepository {

	/////

	public function findUserIdsById($id) {
		return $this->getEntityManager()->getRepository(BaseValue::CLASS_NAME)->findUserIdsByParentEntityTypeAndParentEntityId(Software::TYPE, $id);
	}

	//////

	public function existsByName($name, $excludedId = 0) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(b.id)' ))
			->from($this->getEntityName(), 's')
			->where('s.name = :name')
			->setParameter('name', $name)
		;

		if ($excludedId > 0) {
			$queryBuilder
				->andWhere('s.id != :excludedId')
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
			->select(array( 's', 'mp', 'scrv' ))
			->from($this->getEntityName(), 's')
			->leftJoin('s.mainPicture', 'mp')
			->leftJoin('s.screenshotValues', 'scrv')
			->where('s.id = :id')
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
			->select(array( 's', 'mp' ))
			->from($this->getEntityName(), 's')
			->leftJoin('s.mainPicture', 'mp')
			->where($queryBuilder->expr()->in('s.id', $ids))
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
			->select(array( 's', 'mp' ))
			->from($this->getEntityName(), 's')
			->innerJoin('s.mainPicture', 'mp')
			->where('s.isDraft = false')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('popular-views' == $filter) {
			$queryBuilder
				->addOrderBy('s.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('s.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('s.commentCount', 'DESC')
			;
		} else if ('collaborative-contributors' == $filter) {
			$queryBuilder
				->addOrderBy('s.contributorCount', 'DESC')
			;
		} else if ('order-alphabetical' == $filter) {
			$queryBuilder
				->addOrderBy('s.title', 'ASC')
			;
		}
		$queryBuilder
			->addOrderBy('s.changedAt', 'DESC')
		;
	}

}