<?php

namespace Ladb\CoreBundle\Repository\Core;

use Ladb\CoreBundle\Entity\Core\Tag;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class TagUsageRepository extends AbstractEntityRepository {

	/////

	public function findOneByTagAndEntityType(Tag $tag, $entityType) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'tu' ))
			->from($this->getEntityName(), 'tu')
			->where('tu.tag = :tag')
			->andWhere('tu.entityType = :entityType')
			->setParameter('tag', $tag)
			->setParameter('entityType', $entityType)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByEntityType($entityType, $maxResults) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'tu', 't' ))
			->from($this->getEntityName(), 'tu')
			->innerJoin('tu.tag', 't')
			->andWhere('tu.entityType = :entityType')
			->addOrderBy('tu.score', 'DESC')
			->setParameter('entityType', $entityType)
			->setMaxResults($maxResults)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}