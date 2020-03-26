<?php

namespace Ladb\CoreBundle\Repository\Collection;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Collection\Collection;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Core\View;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class CollectionRepository extends AbstractEntityRepository {

	/////

	public function findOneByIdAndUser($id, User $user = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c' ))
			->from($this->getEntityName(), 'c')
			->where('c.id = :id')
			->setParameter('id', $id)
			->setMaxResults(1)
		;

		// Do not retrieve user viewed tips
		if (!is_null($user)) {
			$queryBuilder
				->leftJoin('LadbCoreBundle:Core\View', 'v', 'WITH', 'v.entityId = c.id AND v.entityType = '.Collection::TYPE.' AND v.kind = :kind AND v.user = :user')
				->andWhere('v.id IS NULL')
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