<?php

namespace App\Repository\Core\Activity;

use App\Repository\AbstractEntityRepository;

class AnswerRepository extends AbstractEntityRepository {

	/////

	public function findByAnswer(\App\Entity\Qa\Answer $answer) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.answer = :answer')
			->setParameter('answer', $answer)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}