<?php

namespace App\Repository\Core;

use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Core\User;
use App\Repository\AbstractEntityRepository;

class MemberInvitationRepository extends AbstractEntityRepository {

	//////

	public function existsByTeamAndRecipient(User $team, User $recipient) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(e)' ))
			->from($this->getEntityName(), 'e')
			->where('e.team = :team')
			->andWhere('e.recipient = :recipient')
			->setParameter('team', $team)
			->setParameter('recipient', $recipient)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	//////

	public function findOneByTeamAndRecipient(User $team, User $recipient) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($this->getEntityName(), 'e')
			->where('e.team = :team')
			->andWhere('e.recipient = :recipient')
			->setParameter('team', $team)
			->setParameter('recipient', $recipient)
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
			->innerJoin('e.recipient', 'u')
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

	public function findPaginedByRecipient(User $recipient, $offset = 0, $limit = 0, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'u' ))
			->from($this->getEntityName(), 'e')
			->innerJoin('e.team', 'u')
			->where('e.recipient = :recipient')
			->setParameter('recipient', $recipient)
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