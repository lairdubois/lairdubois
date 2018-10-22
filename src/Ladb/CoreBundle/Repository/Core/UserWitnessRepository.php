<?php

namespace Ladb\CoreBundle\Repository\Core;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class UserWitnessRepository extends AbstractEntityRepository {

	/////

	public function existsNewerFromDate($date) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(e.id)' ))
			->from($this->getEntityName(), 'e')
			->where('e.createdAt > :date')
			->setParameter('date', $date)
		;
		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return false;
		}
	}


}