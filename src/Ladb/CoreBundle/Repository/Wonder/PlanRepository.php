<?php

namespace Ladb\CoreBundle\Repository\Wonder;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Wonder\Workshop;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class PlanRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ), array( 'inner', 'mainPicture', 'mp' ) );
	}

	/////

	public function findOneByIdJoinedOnUser($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->where('p.id = :id')
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
			->select(array( 'p', 'u', 'mp', 'ps', 'cts', 'wks', 'hws', 'l' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->leftJoin('p.pictures', 'ps')
			->leftJoin('p.creations', 'cts')
			->leftJoin('p.workshops', 'wks')
			->leftJoin('p.howtos', 'hws')
			->leftJoin('p.license', 'l')
			->where('p.id = :id')
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
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->where('p.isDraft = false')
			->andWhere('p.user = :user')
			->orderBy('p.id', 'ASC')
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
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->where('p.isDraft = false')
			->andWhere('p.user = :user')
			->orderBy('p.id', 'DESC')
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
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->where('p.isDraft = false')
			->andWhere('p.user = :user')
			->andWhere('p.id < :id')
			->orderBy('p.id', 'DESC')
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
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->where('p.isDraft = false')
			->andWhere('p.user = :user')
			->andWhere('p.id > :id')
			->orderBy('p.id', 'ASC')
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
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->where($queryBuilder->expr()->in('p.id', $ids))
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPagined($offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->where('p.isDraft = false')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('popular-views' == $filter) {
			$queryBuilder
				->addOrderBy('p.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('p.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('p.commentCount', 'DESC')
			;
		} else if ('popular-downloads' == $filter) {
			$queryBuilder
				->addOrderBy('p.downloadCount', 'DESC')
			;
		} else if ('content-creations' == $filter) {
			$queryBuilder
				->addOrderBy('p.creationCount', 'DESC')
			;
		} else if ('content-workshops' == $filter) {
			$queryBuilder
				->addOrderBy('p.workshopCount', 'DESC')
			;
		} else if ('content-howtos' == $filter) {
			$queryBuilder
				->addOrderBy('p.howtoCount', 'DESC')
			;
		} else if ('collaborative-inspirations' == $filter) {
			$queryBuilder
				->addOrderBy('p.reboundCount', 'DESC')
			;
		} else if ('collaborative-rebounds' == $filter) {
			$queryBuilder
				->addOrderBy('p.inspirationCount', 'DESC')
			;
		} else if ('license-by' == $filter) {
			$queryBuilder
				->innerJoin('p.license', 'l')
				->andWhere('l.allowDerivs = 1')
				->andWhere('l.shareAlike = 0')
				->andWhere('l.allowCommercial = 1')
			;
		} else if ('license-by-nc' == $filter) {
			$queryBuilder
				->innerJoin('p.license', 'l')
				->andWhere('l.allowDerivs = 1')
				->andWhere('l.shareAlike = 0')
				->andWhere('l.allowCommercial = 0')
			;
		} else if ('license-by-nc-nd' == $filter) {
			$queryBuilder
				->innerJoin('p.license', 'l')
				->andWhere('l.allowDerivs = 0')
				->andWhere('l.shareAlike = 0')
				->andWhere('l.allowCommercial = 0')
			;
		} else if ('license-by-nc-sa' == $filter) {
			$queryBuilder
				->innerJoin('p.license', 'l')
				->andWhere('l.allowDerivs = 1')
				->andWhere('l.shareAlike = 1')
				->andWhere('l.allowCommercial = 0')
			;
		} else if ('license-by-nd' == $filter) {
			$queryBuilder
				->innerJoin('p.license', 'l')
				->andWhere('l.allowDerivs = 0')
				->andWhere('l.shareAlike = 0')
				->andWhere('l.allowCommercial = 1')
			;
		} else if ('license-by-sa' == $filter) {
			$queryBuilder
				->innerJoin('p.license', 'l')
				->andWhere('l.allowDerivs = 1')
				->andWhere('l.shareAlike = 1')
				->andWhere('l.allowCommercial = 1')
			;
		}
		$queryBuilder
			->addOrderBy('p.changedAt', 'DESC')
		;
	}

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent', $includeDrafts = false) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->where('u = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		if ('draft' == $filter && $includeDrafts) {
			$queryBuilder
				->andWhere('p.isDraft = true')
			;
		} else if (!$includeDrafts) {
			$queryBuilder
				->andWhere('p.isDraft = false')
			;
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByCreation(Creation $creation, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp', 'c' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->innerJoin('p.creations', 'c')
			->where('p.isDraft = false')
			->andWhere('c = :creation')
			->setParameter('creation', $creation)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByWorkshop(Workshop $workshop, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp', 'w' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->innerJoin('p.workshops', 'w')
			->where('p.isDraft = false')
			->andWhere('w = :workshop')
			->setParameter('workshop', $workshop)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByHowto(Howto $howto, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp', 'h' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->innerJoin('p.howtos', 'h')
			->where('p.isDraft = false')
			->andWhere('h = :howto')
			->setParameter('howto', $howto)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByInspiration(Plan $inspiration, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp', 'i' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->innerJoin('p.inspirations', 'i')
			->where('p.isDraft = false')
			->andWhere('i = :inspiration')
			->setParameter('inspiration', $inspiration)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByRebound(Plan $rebound, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp', 'r' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->innerJoin('p.rebounds', 'r')
			->where('p.isDraft = false')
			->andWhere('r = :rebound')
			->setParameter('rebound', $rebound)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}