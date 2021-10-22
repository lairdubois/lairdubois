<?php

namespace App\Repository\Workflow;

use App\Entity\Workflow\Label;
use App\Entity\Workflow\Part;
use App\Repository\AbstractEntityRepository;

class TaskRepository extends AbstractEntityRepository {

	/////

	public function findByPart(Part $part) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't' ))
			->from($this->getEntityName(), 't')
			->where(':part MEMBER OF t.parts')
			->setParameter('part', $part)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

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