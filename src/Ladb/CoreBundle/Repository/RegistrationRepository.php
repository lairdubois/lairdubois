<?php

namespace Ladb\CoreBundle\Repository;

use Ladb\CoreBundle\Entity\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class RegistrationRepository extends AbstractEntityRepository {

	/////

	public function findOneByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from($this->getEntityName(), 'r')
			->where('r.user = :user')
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByClientIp4($clientIp4) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from($this->getEntityName(), 'r')
			->where('r.clientIp4 = :clientIp4')
			->setParameter('clientIp4', $clientIp4)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}