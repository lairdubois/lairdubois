<?php

namespace Ladb\CoreBundle\Repository\Knowledge\Book;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class ReviewRepository extends AbstractEntityRepository {

	public function findByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r', 'b' ))
			->from($this->getEntityName(), 'r')
			->innerJoin('r.book', 'b')
			->where('r.user = :user')
			->setParameter('user', $user)
			->orderBy('r.score', 'DESC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}