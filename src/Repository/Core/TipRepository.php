<?php

namespace App\Repository\Core;

use App\Entity\Core\Tip;
use App\Entity\Core\User;
use App\Entity\Core\View;
use App\Entity\Offer\Offer;
use App\Repository\AbstractEntityRepository;

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
				->leftJoin('App\Entity\Core\View', 'v', 'WITH', 'v.entityId = t.id AND v.entityType = '.Tip::TYPE.' AND v.kind = :kind AND v.user = :user')
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