<?php

namespace Ladb\CoreBundle\Repository\Core\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class ReviewRepository extends AbstractEntityRepository {

	/////

	public function findByOldReview(\Ladb\CoreBundle\Entity\Knowledge\Book\Review $review) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from($this->getEntityName(), 'r')
			->where('r.review = :review')
			->setParameter('review', $review)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByReview(\Ladb\CoreBundle\Entity\Core\Review $review) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from($this->getEntityName(), 'r')
			->where('r.review = :review')
			->setParameter('review', $review)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}