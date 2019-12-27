<?php

namespace Ladb\CoreBundle\Repository\Core;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Core\View;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class TipRepository extends AbstractEntityRepository {

	public function findOneRandomByUser(User $user = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't' ))
			->from($this->getEntityName(), 't')
			->addSelect('RAND() as HIDDEN rand')
			->orderBy('rand')
			->setMaxResults(1)
		;

		// Do not retrieve user viewed tips
		if (!is_null($user)) {
			$queryBuilder
				->leftJoin('LadbCoreBundle:Core\View', 'v', 'WITH', 'v.entityId = t.id AND v.entityType = 5 AND v.kind = :kind AND v.user = :user')	/* 5 = Tip::TYPE */
				->where('v.id IS NULL')
				->setParameter('user', $user)
				->setParameter('kind', View::KIND_SHOWN)
			;
		}

		try {
			$result = $queryBuilder->getQuery()->getResult();
			if (count($result) > 0) {
				return $result[0];
			}
			return null;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}