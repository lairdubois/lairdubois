<?php

namespace App\Repository\Workflow;

use App\Entity\User;
use App\Model\ViewableInterface;
use App\Repository\AbstractEntityRepository;
use App\Utils\TypableUtils;

class LabelRepository extends AbstractEntityRepository {

	/////

	public function findOneByWorkflowIdAndNameAndColor($workflow, $name, $color) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'l' ))
			->from($this->getEntityName(), 'l')
			->where('l.workflow = :workflow')
			->andWhere('l.name = :name')
			->andWhere('l.color = :color')
			->setParameter('workflow', $workflow)
			->setParameter('name', $name)
			->setParameter('color', $color)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}