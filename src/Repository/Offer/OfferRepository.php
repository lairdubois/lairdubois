<?php

namespace App\Repository\Offer;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Core\View;
use App\Model\HiddableInterface;
use App\Repository\AbstractEntityRepository;
use App\Entity\Offer\Offer;
use App\Entity\Core\User;

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
			->select(array( 'o', 'u', 'uav', 'mp', 'bbs', 'tgs' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->innerJoin('u.avatar', 'uav')
			->leftJoin('o.mainPicture', 'mp')
			->leftJoin('o.bodyBlocks', 'bbs')
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

	public function findOneRandomByCategoryAndUser($category, User $user = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o' ))
			->from($this->getEntityName(), 'o')
			->addSelect('RAND() as HIDDEN rand')
			->where('o.category = :category')
			->andWhere('o.visibility = '.HiddableInterface::VISIBILITY_PUBLIC)
			->setParameter('category', $category)
			->orderBy('rand')
			->setMaxResults(1)
		;

		// Do not retrieve user viewed tips
		if (!is_null($user)) {
			$queryBuilder
				->leftJoin('App\Entity\Core\View', 'v', 'WITH', 'v.entityId = o.id AND v.entityType = :entityType AND v.kind = :kind AND v.user = :user')
				->andWhere('v.id IS NULL')
				->setParameter('entityType', Offer::TYPE)
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

	public function findByIds(array $ids) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where($queryBuilder->expr()->in('o.id', $ids))
		;

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