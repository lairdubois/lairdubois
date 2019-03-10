<?php

namespace Ladb\CoreBundle\Repository\Collection;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class CollectionRepository extends AbstractEntityRepository {

	/////

	public function findPaginedByUser(User $user, $offset, $limit) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->where('c.user = :user')
			->setParameter('user', $user)
			->orderBy('c.updatedAt', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		return new Paginator($queryBuilder->getQuery());
	}

}