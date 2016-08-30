<?php

namespace Ladb\CoreBundle\Repository;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class SpotlightRepository extends AbstractEntityRepository {

	/////

	public function findOneLast() {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 's' ))
			->from($this->getEntityName(), 's')
			->where('s.enabled = 1')
			->andWhere('s.finishedAt IS NULL')
			->orderBy('s.createdAt', 'ASC')
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}