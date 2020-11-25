<?php

namespace Ladb\CoreBundle\Repository\Core\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class RequestRepository extends AbstractEntityRepository {

	/////

	public function findByRequest(\Ladb\CoreBundle\Entity\Core\MemberRequest $request) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.request = :request')
			->setParameter('request', $request)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}