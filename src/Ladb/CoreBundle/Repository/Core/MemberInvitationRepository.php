<?php

namespace Ladb\CoreBundle\Repository\Core;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

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

}