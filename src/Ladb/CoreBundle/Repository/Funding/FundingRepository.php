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

	public function findOneByMonthAndYear($month, $year) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', ))
			->from($this->getEntityName(), 'f')
			->where('f.month = :month')
			->andWhere('f.year = :year')
			->setParameter('month', $month)
			->setParameter('year', $year)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}


}