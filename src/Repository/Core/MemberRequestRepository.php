<?php

namespace App\Repository\Core;

use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Core\User;
use App\Repository\AbstractEntityRepository;

class MemberRequestRepository extends AbstractEntityRepository {

	//////

	public function existsByTeamAndSender(User $team, User $sender) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(e)' ))
			->from($this->getEntityName(), 'e')
			->where('e.team = :team')
			->andWhere('e.sender = :sender')
			->setParameter('team', $team)
			->setParameter('sender', $sender)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	//////

	public function findOneByTeamAndSender(User $team, User $sender) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($this->getEntityName(), 'e')
			->where('e.team = :team')
			->andWhere('e.sender = :sender')
			->setParameter('team', $team)
			->setParameter('sender', $sender)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	//////

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		$queryBuilder
			->addOrderBy('u.createdAt', 'DESC')
		;
	}

	public function findPaginedByTeam(User $team, $offset = 0, $limit = 0, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'u' ))
			->from($this->getEntityName(), 'e')
			->innerJoin('e.sender', 'u')
			->where('e.team = :team')
			->setParameter('team', $team)
		;

		if ($offset > 0) {
			$queryBuilder->setFirstResult($offset);
		}
		if ($limit > 0) {
			$queryBuilder->setMaxResults($limit);
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedBySender(User $sender, $offset = 0, $limit = 0, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'u' ))
			->from($this->getEntityName(), 'e')
			->innerJoin('e.team', 'u')
			->where('e.sender = :sender')
			->setParameter('sender', $sender)
		;

		if ($offset > 0) {
			$queryBuilder->setFirstResult($offset);
		}
		if ($limit > 0) {
			$queryBuilder->setMaxResults($limit);
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}