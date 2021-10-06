<?php

namespace App\Repository\Core\Activity;

use App\Repository\AbstractEntityRepository;

class LikeRepository extends AbstractEntityRepository {

	/////

	public function findByLike(\App\Entity\Core\Like $like) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.like = :like')
			->setParameter('like', $like)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}