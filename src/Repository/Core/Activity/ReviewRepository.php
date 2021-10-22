<?php

namespace App\Repository\Core\Activity;

use App\Repository\AbstractEntityRepository;

class ReviewRepository extends AbstractEntityRepository {

	/////

	public function findByReview(\App\Entity\Core\Review $review) {
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