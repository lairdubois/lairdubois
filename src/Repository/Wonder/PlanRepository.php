<?php

namespace App\Repository\Wonder;

use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Howto\Howto;
use App\Entity\Core\User;
use App\Entity\Knowledge\School;
use App\Entity\Qa\Question;
use App\Entity\Wonder\Creation;
use App\Entity\Wonder\Plan;
use App\Entity\Wonder\Workshop;
use App\Entity\Workflow\Workflow;
use App\Repository\AbstractEntityRepository;

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
			->select(array( 'p', 'u', 'uav', 'mp', 'ps', 'cts', 'wks', 'hws', 'wfs', 'tgs', 'l' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('u.avatar', 'uav')
			->innerJoin('p.mainPicture', 'mp')
			->leftJoin('p.pictures', 'ps')
			->leftJoin('p.creations', 'cts')
			->leftJoin('p.workshops', 'wks')
			->leftJoin('p.howtos', 'hws')
			->leftJoin('p.workflows', 'wfs')
			->leftJoin('p.tags', 'tgs')
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
		}
		$queryBuilder
			->addOrderBy('p.changedAt', 'DESC')
		;
	}

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

	public function findPaginedByQuestion(Question $question, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp', 'q' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->innerJoin('p.questions', 'q')
			->where('p.isDraft = false')
			->andWhere('q = :question')
			->setParameter('question', $question)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

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

	public function findPaginedByWorkflow(Workflow $workflow, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp', 'w' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->innerJoin('p.workflows', 'w')
			->where('p.isDraft = false')
			->andWhere('w = :workflow')
			->setParameter('workflow', $workflow)
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

	public function findPaginedBySchool(School $school, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'u', 'mp', 's' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.mainPicture', 'mp')
			->innerJoin('p.schools', 's')
			->where('p.isDraft = false')
			->andWhere('s = :school')
			->setParameter('school', $school)
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