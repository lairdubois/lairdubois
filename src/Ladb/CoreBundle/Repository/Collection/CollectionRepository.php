<?php

namespace Ladb\CoreBundle\Repository\Collection;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class CollectionRepository extends AbstractEntityRepository {

	/////

	public function findPaginedByUser(User $user, $offset, $limit) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->where('c.user = :user')
			->setParameter('user', $user)
			->orderBy('c.title', 'ASC')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByEntity(CollectionnableInterface $entity, $offset, $limit) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'es' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.entries', 'es')
			->where('es.entityType = :entityType')
			->andWhere('es.entityId = :entityId')
			->andWhere('c.visibility = :visibility')
			->setParameter('entityType', $entity->getType())
			->setParameter('entityId', $entity->getId())
			->setParameter('visibility', HiddableInterface::VISIBILITY_PUBLIC)
			->orderBy('c.changedAt', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		return new Paginator($queryBuilder->getQuery());
	}

}