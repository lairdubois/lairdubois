<?php

namespace Ladb\CoreBundle\Repository\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class FollowRepository extends AbstractEntityRepository {

	/////

	public function findByFollower(\Ladb\CoreBundle\Entity\Core\Follower $follower) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.follower = :follower')
			->setParameter('follower', $follower)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}