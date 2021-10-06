<?php

namespace App\Repository\Knowledge\Wood;

use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Knowledge\Value\Picture;
use App\Entity\Knowledge\Wood;
use App\Repository\AbstractEntityRepository;

class TextureRepository extends AbstractEntityRepository {

	public function existsByWoodAndValue(Wood $wood, Picture $value) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(m.id)' ))
			->from($this->getEntityName(), 'm')
			->where('m.wood = :wood')
			->andWhere('m.value = :value')
			->setParameter('wood', $wood)
			->setParameter('value', $value)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return false;
		}
	}

	/////

	public function findOneByWoodAndValue(Wood $wood, Picture $value) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'm' ))
			->from($this->getEntityName(), 'm')
			->where('m.wood = :wood')
			->andWhere('m.value = :value')
			->setParameter('wood', $wood)
			->setParameter('value', $value)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('grain' == $filter) {
			$queryBuilder
				->andWhere('v.parentEntityField = :field')
				->setParameter('field', Wood::FIELD_GRAIN)
			;
		} else if ('endgrain' == $filter) {
			$queryBuilder
				->andWhere('v.parentEntityField = :field')
				->setParameter('field', Wood::FIELD_ENDGRAIN)
			;
		}
		$queryBuilder
			->andWhere('v.voteScore >= 0')
			->addOrderBy('v.voteScore', 'DESC')
		;
	}

	public function findPaginedByWood(Wood $wood, $offset, $limit, $filter = 'all') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'm', 'v' ))
			->from($this->getEntityName(), 'm')
			->innerJoin('m.value', 'v')
			->where('m.wood = :wood')
			->setParameter('wood', $wood)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}