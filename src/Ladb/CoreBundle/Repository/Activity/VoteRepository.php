<?php

namespace Ladb\CoreBundle\Repository\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class VoteRepository extends AbstractEntityRepository {

	/////

	public function findByVote(\Ladb\CoreBundle\Entity\Vote $vote) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.vote = :vote')
			->setParameter('vote', $vote)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}