<?php

namespace Ladb\CoreBundle\Repository\Workflow;

use Ladb\CoreBundle\Entity\Workflow\Label;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class TaskRepository extends AbstractEntityRepository {

	/////

	public function findByLabel(Label $label) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't' ))
			->from($this->getEntityName(), 't')
			->where(':label MEMBER OF t.labels')
			->setParameter('label', $label)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}