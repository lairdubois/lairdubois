<?php

namespace Ladb\CoreBundle\Repository\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class LikeRepository extends AbstractEntityRepository {

	/////

	public function findByLike(\Ladb\CoreBundle\Entity\Core\Like $like) {
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