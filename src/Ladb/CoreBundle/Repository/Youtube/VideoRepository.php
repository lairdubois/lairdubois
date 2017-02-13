<?php

namespace Ladb\CoreBundle\Repository\Youtube;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class VideoRepository extends AbstractEntityRepository {

	/////

	public function existsByEmbedIdentifier($embedIdentifier) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(v.id)' ))
			->from($this->getEntityName(), 'v')
			->where('v.embedIdentifier = :embedIdentifier')
			->setParameter('embedIdentifier', $embedIdentifier)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findOneByEmbedIdentifier($embedIdentifier) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'v' ))
			->from($this->getEntityName(), 'v')
			->where('v.embedIdentifier = :embedIdentifier')
			->setParameter('embedIdentifier', $embedIdentifier)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}