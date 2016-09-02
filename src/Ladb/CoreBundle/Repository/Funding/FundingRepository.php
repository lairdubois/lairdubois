<?php

namespace Ladb\CoreBundle\Repository\Funding;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;
use Ladb\CoreBundle\Entity\User;

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
			->orderBy('f.month', 'DESC')
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}


}