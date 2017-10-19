<?php

namespace Ladb\CoreBundle\Repository\Knowledge\School;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class TestimonialRepository extends AbstractEntityRepository {

	public function findByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't', 's' ))
			->from($this->getEntityName(), 't')
			->innerJoin('t.school', 's')
			->where('t.user = :user')
			->setParameter('user', $user)
			->orderBy('t.fromYear', 'DESC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}