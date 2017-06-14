<?php

namespace Ladb\CoreBundle\Repository\Wonder;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Knowledge\Provider;
use Ladb\CoreBundle\Entity\User;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;
use Ladb\CoreBundle\Utils\SearchUtils;

class CreationRepository extends AbstractEntityRepository {

	public function createIsNotDraftQueryBuilder() {
		return $this->createQueryBuilder('a')->where('a.isDraft = false');	// FOSElasticaBundle bug -> use 'a'
	}

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ), array( 'inner', 'mainPicture', 'mp' ) );
	}

	/////

	public function findOneByIdJoinedOnUser($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->where('c.id = :id')
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
			->select(array( 'c', 'u', 'mp', 'sp', 'ps', 'bbs', 'pls', 'hws', 'l' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->leftJoin('c.spotlight', 'sp')
			->leftJoin('c.pictures', 'ps')
			->leftJoin('c.bodyBlocks', 'bbs')
			->leftJoin('c.plans', 'pls')
			->leftJoin('c.howtos', 'hws')
			->leftJoin('c.license', 'l')
			->where('c.id = :id')
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
			->select(array( 'c', 'u', 'mp', 'sp' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->leftJoin('c.spotlight', 'sp')		// To avoid extra request due to OneToOne relation
			->where('c.isDraft = false')
			->andWhere('c.user = :user')
			->orderBy('c.id', 'ASC')
			->setParameter('user', $user)
			->setMaxResults(1)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneLastByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp', 'sp' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->leftJoin('c.spotlight', 'sp')		// To avoid extra request due to OneToOne relation
			->where('c.isDraft = false')
			->andWhere('c.user = :user')
			->orderBy('c.id', 'DESC')
			->setParameter('user', $user)
			->setMaxResults(1)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOnePreviousByUserAndId(User $user, $id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp', 'sp' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->leftJoin('c.spotlight', 'sp')		// To avoid extra request due to OneToOne relation
			->where('c.isDraft = false')
			->andWhere('c.user = :user')
			->andWhere('c.id < :id')
			->orderBy('c.id', 'DESC')
			->setParameter('user', $user)
			->setParameter('id', $id)
			->setMaxResults(1)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneNextByUserAndId(User $user, $id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp', 'sp' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->leftJoin('c.spotlight', 'sp')		// To avoid extra request due to OneToOne relation
			->where('c.isDraft = false')
			->andWhere('c.user = :user')
			->andWhere('c.id > :id')
			->orderBy('c.id', 'ASC')
			->setParameter('user', $user)
			->setParameter('id', $id)
			->setMaxResults(1)
		;

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
			->select(array( 'c', 'u', 'mp', 'sp' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->leftJoin('c.spotlight', 'sp')		// To avoid extra request due to OneToOne relation
			->where($queryBuilder->expr()->in('c.id', $ids))
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPagined($offset, $limit, $filter = 'recent', $filterParam = null, $excludedIds = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp', 'sp' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->leftJoin('c.spotlight', 'sp')
			->where('c.isDraft = false')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		if (!is_null($excludedIds)) {
			$queryBuilder
				->andWhere('c.id NOT IN ('.implode(',', $excludedIds).')')
			;
		}

		if ('followed' == $filter) {
			$queryBuilder
				->innerJoin('u.followers', 'f', 'WITH', 'f.user = :filterParam')
				->setParameter('filterParam', $filterParam);
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('popular-views' == $filter) {
			$queryBuilder
				->addOrderBy('c.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('c.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('c.commentCount', 'DESC')
			;
		} else if ('popular-spotlights' == $filter) {
			$queryBuilder
				->innerJoin('c.spotlight', 's')
				->andWhere('s.enabled = 1')
				->addOrderBy('s.createdAt', 'DESC')
			;
		} else if ('content-plans' == $filter) {
			$queryBuilder
				->addOrderBy('c.planCount', 'DESC')
			;
		} else if ('content-howtos' == $filter) {
			$queryBuilder
				->addOrderBy('c.howtoCount', 'DESC')
			;
		} else if ('content-videos' == $filter) {
			$queryBuilder
				->addOrderBy('c.bodyBlockVideoCount', 'DESC')
			;
		} else if ('collaborative-inspirations' == $filter) {
			$queryBuilder
				->addOrderBy('c.reboundCount', 'DESC')
			;
		} else if ('collaborative-rebounds' == $filter) {
			$queryBuilder
				->addOrderBy('c.inspirationCount', 'DESC')
			;
		} else if ('license-by' == $filter) {
			$queryBuilder
				->innerJoin('c.license', 'l')
				->andWhere('l.allowDerivs = 1')
				->andWhere('l.shareAlike = 0')
				->andWhere('l.allowCommercial = 1')
			;
		} else if ('license-by-nc' == $filter) {
			$queryBuilder
				->innerJoin('c.license', 'l')
				->andWhere('l.allowDerivs = 1')
				->andWhere('l.shareAlike = 0')
				->andWhere('l.allowCommercial = 0')
			;
		} else if ('license-by-nc-nd' == $filter) {
			$queryBuilder
				->innerJoin('c.license', 'l')
				->andWhere('l.allowDerivs = 0')
				->andWhere('l.shareAlike = 0')
				->andWhere('l.allowCommercial = 0')
			;
		} else if ('license-by-nc-sa' == $filter) {
			$queryBuilder
				->innerJoin('c.license', 'l')
				->andWhere('l.allowDerivs = 1')
				->andWhere('l.shareAlike = 1')
				->andWhere('l.allowCommercial = 0')
			;
		} else if ('license-by-nd' == $filter) {
			$queryBuilder
				->innerJoin('c.license', 'l')
				->andWhere('l.allowDerivs = 0')
				->andWhere('l.shareAlike = 0')
				->andWhere('l.allowCommercial = 1')
			;
		} else if ('license-by-sa' == $filter) {
			$queryBuilder
				->innerJoin('c.license', 'l')
				->andWhere('l.allowDerivs = 1')
				->andWhere('l.shareAlike = 1')
				->andWhere('l.allowCommercial = 1')
			;
		}
		$queryBuilder
			->addOrderBy('c.changedAt', 'DESC')
		;
	}

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent', $includeDrafts = false) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->where('c.user = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		if ('draft' == $filter && $includeDrafts) {
			$queryBuilder
				->andWhere('c.isDraft = true')
			;
		} else if (!$includeDrafts) {
			$queryBuilder
				->andWhere('c.isDraft = false')
			;
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByPlan(Plan $plan, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp', 'p' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->innerJoin('c.plans', 'p')
			->where('c.isDraft = false')
			->andWhere('p = :plan')
			->setParameter('plan', $plan)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByHowto(Howto $howto, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp', 'h' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->innerJoin('c.howtos', 'h')
			->where('c.isDraft = false')
			->andWhere('h = :howto')
			->setParameter('howto', $howto)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByProvider(Provider $provider, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp', 'p' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->innerJoin('c.providers', 'p')
			->where('c.isDraft = false')
			->andWhere('p = :provider')
			->setParameter('provider', $provider)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByInspiration(Creation $inspiration, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp', 'i' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->innerJoin('c.inspirations', 'i')
			->where('c.isDraft = false')
			->andWhere('i = :inspiration')
			->setParameter('inspiration', $inspiration)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByRebound(Creation $rebound, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'mp', 'r' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->innerJoin('c.mainPicture', 'mp')
			->innerJoin('c.rebounds', 'r')
			->where('c.isDraft = false')
			->andWhere('r = :rebound')
			->setParameter('rebound', $rebound)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}