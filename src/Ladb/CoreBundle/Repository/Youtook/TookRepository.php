<?php

namespace Ladb\CoreBundle\Repository\Youtook;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class TookRepository extends AbstractEntityRepository {

	/////

	public function existsByEmbedIdentifierAndUser($embedIdentifier, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(t.id)' ))
			->from($this->getEntityName(), 't')
			->where('t.embedIdentifier = :embedIdentifier')
			->andWhere('t.user = :user')
			->setParameter('embedIdentifier', $embedIdentifier)
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findOneByEmbedIdentifierAndUser($embedIdentifier, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't' ))
			->from($this->getEntityName(), 't')
			->where('t.embedIdentifier = :embedIdentifier')
			->andWhere('t.user = :user')
			->setParameter('embedIdentifier', $embedIdentifier)
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		$queryBuilder
			->addOrderBy('t.changedAt', 'DESC')
		;
	}

	public function findPagined($offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't', 'th' ))
			->from($this->getEntityName(), 't')
			->innerJoin('t.user', 'u')
			->innerJoin('t.thumbnail', 'th')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't', 'u', 'mp' ))
			->from($this->getEntityName(), 't')
			->innerJoin('t.user', 'u')
			->innerJoin('t.mainPicture', 'mp')
			->where('t.user = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}