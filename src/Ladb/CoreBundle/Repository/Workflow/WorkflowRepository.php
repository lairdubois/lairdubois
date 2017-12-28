<?php

namespace Ladb\CoreBundle\Repository\Workflow;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class WorkflowRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ), array( 'inner', 'mainPicture', 'mp' ) );
	}

	/////

	public function findByIds(array $ids) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 'u', 'uav', 'mp' ))
			->from($this->getEntityName(), 'w')
			->innerJoin('w.user', 'u')
			->innerJoin('u.avatar', 'uav')
			->leftJoin('w.mainPicture', 'mp')
			->where($queryBuilder->expr()->in('w.id', $ids))
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPagined($offset, $limit, $filter = 'all') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'w' ))
			->from($this->getEntityName(), 'w')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$queryBuilder
			->addOrderBy('w.createdAt', 'DESC');

		return new Paginator($queryBuilder->getQuery());
	}

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