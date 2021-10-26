<?php

namespace App\Repository\Event;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Core\View;
use App\Repository\AbstractEntityRepository;
use App\Entity\Event\Event;
use App\Entity\Core\User;

class EventRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ) );
	}

	/////

	public function findOneByIdJoinedOnUser($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->where('f.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneByIdJoinedOnOptimized($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u', 'uav', 'mp', 'bbs', 'tgs' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->leftJoin('u.avatar', 'uav')
			->innerJoin('f.mainPicture', 'mp')
			->leftJoin('f.bodyBlocks', 'bbs')
			->leftJoin('f.tags', 'tgs')
			->where('f.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneFirstByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u', 'mp' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->leftJoin('f.mainPicture', 'mp')
			->where('f.isDraft = false')
			->andWhere('f.user = :user')
			->orderBy('f.id', 'ASC')
			->setParameter('user', $user)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneLastByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u', 'mp' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->leftJoin('f.mainPicture', 'mp')
			->where('f.isDraft = false')
			->andWhere('f.user = :user')
			->orderBy('f.id', 'DESC')
			->setParameter('user', $user)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOnePreviousByUserAndId(User $user, $id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u', 'mp' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->leftJoin('f.mainPicture', 'mp')
			->where('f.isDraft = false')
			->andWhere('f.user = :user')
			->andWhere('f.id < :id')
			->orderBy('f.id', 'DESC')
			->setParameter('user', $user)
			->setParameter('id', $id)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneNextByUserAndId(User $user, $id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u', 'mp' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->leftJoin('f.mainPicture', 'mp')
			->where('f.isDraft = false')
			->andWhere('f.user = :user')
			->andWhere('f.id > :id')
			->orderBy('f.id', 'ASC')
			->setParameter('user', $user)
			->setParameter('id', $id)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByIds(array $ids) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 'u', 'mp' ))
			->from($this->getEntityName(), 'f')
			->innerJoin('f.user', 'u')
			->innerJoin('f.mainPicture', 'mp')
			->where($queryBuilder->expr()->in('f.id', $ids))
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findByRunningNowByUser(User $user = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'u', 'mp' ))
			->from($this->getEntityName(), 'e')
			->innerJoin('e.user', 'u')
			->innerJoin('e.mainPicture', 'mp')
			->where('e.createdAt > :limitDate')
			->andWhere('e.highlightable = true')
			->andWhere('e.isDraft = false')
			->andWhere('e.cancelled = false')
			->andWhere('e.startAt <= :now')
			->andWhere('e.endAt >= :now')
			->setParameter('limitDate', (new \DateTime())->sub(new \DateInterval('P1Y')))	// Limit search to 1 year ago
			->setParameter('now', new \DateTime())
		;

		// Do not retrieve user viewed tips
		if (!is_null($user)) {
			$queryBuilder
				->leftJoin('App\Entity\Core\View', 'v', 'WITH', 'v.entityId = e.id AND v.entityType = '.Event::TYPE.' AND v.kind = :kind AND v.user = :user')
				->andWhere('v.id IS NULL')
				->setParameter('user', $user)
				->setParameter('kind', View::KIND_SHOWN)
			;
		}

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('popular-views' == $filter) {
			$queryBuilder
				->addOrderBy('f.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('f.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('f.commentCount', 'DESC')
			;
		}
		$queryBuilder
			->addOrderBy('f.changedAt', 'DESC')
		;
	}

}