<?php

namespace App\Repository\Message;

use App\Entity\Core\User;
use App\Entity\Message\Message;
use App\Repository\AbstractEntityRepository;

class MessageMetaRepository extends AbstractEntityRepository {

	/////

	public function findOneByMessageAndParticipant(Message $message, User $participant) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'mm' ))
			->from($this->getEntityName(), 'mm')
			->where('mm.message = :message')
			->andWhere('mm.participant = :participant')
			->setParameter('message', $message)
			->setParameter('participant', $participant)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByParticipant(User $participant) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'mm', 'p' ))
			->from($this->getEntityName(), 'mm')
			->innerJoin('mm.participant', 'p')
			->andWhere('p = :participant')
			->setParameter('participant', $participant)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}