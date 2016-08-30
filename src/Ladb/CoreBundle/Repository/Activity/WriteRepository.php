<?php

namespace Ladb\CoreBundle\Repository\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class WriteRepository extends AbstractEntityRepository {

	/////

	public function findByMessage(\Ladb\CoreBundle\Entity\Message $message) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.message = :message')
			->setParameter('message', $message)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}