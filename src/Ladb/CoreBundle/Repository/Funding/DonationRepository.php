<?php

namespace Ladb\CoreBundle\Repository\Funding;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;
use Ladb\CoreBundle\Entity\User;

class DonationRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ) );
	}

	/////

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('generous' == $filter) {
			$queryBuilder
				->addOrderBy('d.amount', 'DESC')
			;
		}

		$queryBuilder
			->addOrderBy('d.createdAt', 'DESC')
		;
	}

	public function findPagined($offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'd', 'u' ))
			->from($this->getEntityName(), 'd')
			->innerJoin('d.user', 'u')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent', $includeDrafts = false) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'd', 'u' ))
			->from($this->getEntityName(), 'd')
			->innerJoin('d.user', 'u')
			->where('u = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter, true);

		return new Paginator($queryBuilder->getQuery());
	}

}