<?php

namespace Ladb\CoreBundle\Repository\Workflow;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class WorkflowRepository extends AbstractEntityRepository {

	/////

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'all') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'w' ))
			->from($this->getEntityName(), 'w')
			->where('w.user = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$queryBuilder
			->addOrderBy('w.createdAt', 'DESC');

		return new Paginator($queryBuilder->getQuery());
	}

}