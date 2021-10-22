<?php

namespace App\Repository\Core\Activity;

use App\Repository\AbstractEntityRepository;

class MentionRepository extends AbstractEntityRepository {

	/////

	public function findByLike(\App\Entity\Core\Mention $mention) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.mention = :mention')
			->setParameter('mention', $mention)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}