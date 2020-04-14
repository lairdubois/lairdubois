<?php

namespace Ladb\CoreBundle\Repository\Knowledge;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue;
use Ladb\CoreBundle\Entity\Knowledge\Wood;

class WoodRepository extends AbstractKnowledgeRepository {

	/////

	public function findUserIdsById($id) {
		return $this->getEntityManager()->getRepository(BaseValue::CLASS_NAME)->findUserIdsByParentEntityTypeAndParentEntityId(Wood::TYPE, $id);
	}

	//////

	public function existsByName($name, $excludedId = 0) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(w.id)' ))
			->from($this->getEntityName(), 'w')
			->where('REGEXP(w.name, :regexp) = true')
			->setParameter('regexp', '(^|,)('.$name.')($|,)')
		;

		if ($excludedId > 0) {
			$queryBuilder
				->andWhere('w.id != :excludedId')
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
			->select(array( 'w', 'mp', 'grav', 'edg', 'edgv', 'lmb', 'lmbv', 'tre', 'trev', 'lef', 'lefv', 'brk', 'brkv', 'namv', 'sciv', 'engv', 'famv', 'denv', 'avav', 'priv', 'oriv', 'utiv' ))
			->from($this->getEntityName(), 'w')
			->leftJoin('w.mainPicture', 'mp')
			->leftJoin('w.grainValues', 'grav')
			->leftJoin('w.endgrain', 'edg')
			->leftJoin('w.endgrainValues', 'edgv')
			->leftJoin('w.lumber', 'lmb')
			->leftJoin('w.lumberValues', 'lmbv')
			->leftJoin('w.tree', 'tre')
			->leftJoin('w.treeValues', 'trev')
			->leftJoin('w.leaf', 'lef')
			->leftJoin('w.leafValues', 'lefv')
			->leftJoin('w.bark', 'brk')
			->leftJoin('w.barkValues', 'brkv')
			->leftJoin('w.nameValues', 'namv')
			->leftJoin('w.scientificnameValues', 'sciv')
			->leftJoin('w.englishnameValues', 'engv')
			->leftJoin('w.familyValues', 'famv')
			->leftJoin('w.densityValues', 'denv')
			->leftJoin('w.availabilityValues', 'avav')
			->leftJoin('w.priceValues', 'priv')
			->leftJoin('w.originValues', 'oriv')
			->leftJoin('w.utilizationValues', 'utiv')
			->where('w.id = :id')
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
			->select(array( 'w', 'mp' ))
			->from($this->getEntityName(), 'w')
			->leftJoin('w.mainPicture', 'mp')
			->where($queryBuilder->expr()->in('w.id', $ids))
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
				->addOrderBy('w.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('w.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('w.commentCount', 'DESC')
			;
		} else if ('collaborative-contributors' == $filter) {
			$queryBuilder
				->addOrderBy('w.contributorCount', 'DESC')
			;
		} else if ('order-alphabetical' == $filter) {
			$queryBuilder
				->addOrderBy('w.title', 'ASC')
			;
		} else if ('order-density' == $filter) {
			$queryBuilder
				->addOrderBy('w.density', 'DESC')
			;
		}
		$queryBuilder
			->addOrderBy('w.changedAt', 'DESC')
		;
	}

	public function findPagined($offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 'mp' ))
			->from($this->getEntityName(), 'w')
			->innerJoin('w.mainPicture', 'mp')
			->where('w.isDraft = false')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}