<?php

namespace App\Repository\Funding;

use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Repository\AbstractEntityRepository;
use App\Entity\Core\User;

class FundingRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array();
	}

	/////

	public function findOneByYearAndMonth($year, $month) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', ))
			->from($this->getEntityName(), 'f')
			->where('f.year = :year')
			->andWhere('f.month = :month')
			->setParameter('year', $year)
			->setParameter('month', $month)
			->setMaxResults(1)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneLast() {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', ))
			->from($this->getEntityName(), 'f')
			->orderBy('f.year', 'DESC')
			->addOrderBy('f.month', 'DESC')
			->setMaxResults(1)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}


}