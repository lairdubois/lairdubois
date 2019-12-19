<?php

namespace Ladb\CoreBundle\Repository\Offer;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;
use Ladb\CoreBundle\Entity\Offer\Offer;
use Ladb\CoreBundle\Entity\Core\User;

class OfferRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ) );
	}

	/////

	public function findOneByIdJoinedOnUser($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->where('o.id = :id')
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
			->select(array( 'o', 'u', 'uav', 'mp', 'bbs', 'ct', 'tgs' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->innerJoin('u.avatar', 'uav')
			->innerJoin('o.mainPicture', 'mp')
			->leftJoin('o.bodyBlocks', 'bbs')
			->leftJoin('o.content', 'ct')
			->leftJoin('o.tags', 'tgs')
			->where('o.id = :id')
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
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->andWhere('o.user = :user')
			->orderBy('o.id', 'ASC')
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
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->andWhere('o.user = :user')
			->orderBy('o.id', 'DESC')
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
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->andWhere('o.user = :user')
			->andWhere('o.id < :id')
			->orderBy('o.id', 'DESC')
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
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->andWhere('o.user = :user')
			->andWhere('o.id > :id')
			->orderBy('o.id', 'ASC')
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
			->select(array( 'o', 'u', 'mp', 'ct' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->innerJoin('o.mainPicture', 'mp')
			->leftJoin('o.content', 'ct')
			->where($queryBuilder->expr()->in('o.id', $ids))
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findByRunningNow() {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp', 'ct' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->innerJoin('o.mainPicture', 'mp')
			->innerJoin('o.content', 'ct')
			->where('ct INSTANCE OF \\Ladb\\CoreBundle\\Entity\\Offer\\Content\\Event')
			->andWhere('o.createdAt > :limitDate')
			->andWhere('o.isDraft = false')
			->setParameter('limitDate', (new \DateTime())->sub(new \DateInterval('P1Y')))	// Limit search to 1 year ago
		;

		try {

			// TODO : Do the postreatment in DQL Query

			$now = new \DateTime();
			$offers = $queryBuilder->getQuery()->getResult();
			$runningOffers = array();
			foreach ($offers as $offer) {
				if (!$offer->getContent()->getCancelled()
					&& $offer->getContent()->getStartDate() <= $now 	/* event starts today ? */
					&& $offer->getContent()->getEndAt() >= $now 		/* hide finished events */
					&& $offer->getContent()->getDuration()->d <= 3 	/* limit to 3 days long events */ ) {
					$runningOffers[] = $offer;
				}
			}

			return $runningOffers;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('popular-views' == $filter) {
			$queryBuilder
				->addOrderBy('o.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('o.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('o.commentCount', 'DESC')
			;
		}
		$queryBuilder
			->addOrderBy('o.changedAt', 'DESC')
		;
	}

	public function findPagined($offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent', $includeDrafts = false) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('u = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		if ('draft' == $filter && $includeDrafts) {
			$queryBuilder
				->andWhere('o.isDraft = true')
			;
		} else if (!$includeDrafts) {
			$queryBuilder
				->andWhere('o.isDraft = false')
			;
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}