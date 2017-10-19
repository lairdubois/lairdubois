<?php

namespace Ladb\CoreBundle\Repository\Message;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class ThreadRepository extends AbstractEntityRepository {

	/////

	public function findOneByIdJoinedOnOptimized($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't', 'tm', 'm', 's', 'mm', 'p' ))
			->from($this->getEntityName(), 't')
			->innerJoin('t.metas', 'tm')
			->innerJoin('t.messages', 'm')
			->innerJoin('m.sender', 's')
			->innerJoin('m.metas', 'mm')
			->innerJoin('mm.participant', 'p')
			->where('t.id = :id')
			->setParameter('id', $id);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneByIdJoinedOnMetaAndParticipant($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't', 'tm', 'p' ))
			->from($this->getEntityName(), 't')
			->innerJoin('t.metas', 'tm')
			->innerJoin('tm.participant', 'p')
			->where('t.id = :id')
			->setParameter('id', $id);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function countUnreadMessageByThreadAndUser($thread, User $participant) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select('COUNT(m)')
			->from($this->getEntityName(), 't')
			->innerJoin('t.messages', 'm')
			->innerJoin('m.metas', 'mm')
			->where('t = :thread')
			->andWhere('mm.participant = :participant')
			->andWhere('mm.isRead = false')
			->setParameter('thread', $thread)
			->setParameter('participant', $participant);

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return 0;
		}
	}

	/////

	public function findPaginedByUser($user, $offset, $limit, $filter = 'all') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't' ))
			->from($this->getEntityName(), 't')
			->innerJoin('t.metas', 'tm')
			->innerJoin('tm.participant', 'p')
			->where('p = :user')
			->andWhere('tm.isDeleted = false')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		if ($filter == 'sent') {
			$queryBuilder
				->andWhere('t.createdBy = :createdBy')
				->setParameter('createdBy', $user);
		}

		$queryBuilder
			->addOrderBy('t.lastMessageDate', 'DESC');

		return new Paginator($queryBuilder->getQuery());
	}

}