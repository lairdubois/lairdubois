<?php

namespace Ladb\CoreBundle\Repository\Knowledge;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue;
use Ladb\CoreBundle\Entity\Knowledge\Value\Integer;
use Ladb\CoreBundle\Entity\Knowledge\Value\Picture;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Entity\Knowledge\School;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Repository\Knowledge\Value\BaseValueRepository;

class SchoolRepository extends AbstractKnowledgeRepository {

	public function createIsNotDraftQueryBuilder() {
		return $this->createQueryBuilder('a')->where('a.isDraft = false');	// FOSElasticaBundle bug -> use 'a'
	}

	/////

	public function findUserIdsById($id) {
		return $this->getEntityManager()->getRepository(BaseValue::CLASS_NAME)->findUserIdsByParentEntityTypeAndParentEntityId(School::TYPE, $id);
	}

	//////

	public function existsByName($name, $excludedId = 0) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(s.id)' ))
			->from($this->getEntityName(), 's')
			->where('LOWER(s.name) = :name')
			->setParameter('name', strtolower($name))
		;

		if ($excludedId > 0) {
			$queryBuilder
				->andWhere('s.id != :excludedId')
				->setParameter('excludedId', $excludedId)
			;
		}

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (\Doctrine\ORM\NoResultException $e) {
			return false;
		}
	}

	/////

	public function findOneByIdJoinedOnOptimized($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp', 'logv' ))
			->from($this->getEntityName(), 'p')
			->leftJoin('p.mainPicture', 'mp')
			->leftJoin('p.logoValues', 'logv')
			->where('p.id = :id')
			->setParameter('id', $id)
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
			->select(array( 'p' ))
			->from($this->getEntityName(), 'p')
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
			->select(array( 'p', 'mp' ))
			->from($this->getEntityName(), 'p')
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
		} else if ('collaborative-contributors' == $filter) {
			$queryBuilder
				->addOrderBy('p.contributorCount', 'DESC')
			;
		} else if ('order-alphabetical' == $filter) {
			$queryBuilder
				->addOrderBy('p.title', 'ASC')
			;
		}
		$queryBuilder
			->addOrderBy('p.changedAt', 'DESC')
		;
	}

	public function findPaginedByCreation(Creation $creation, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp', 'c' ))
			->from($this->getEntityName(), 'p')
			->leftJoin('p.mainPicture', 'mp')
			->innerJoin('p.creations', 'c')
			->where('c = :creation')
			->setParameter('creation', $creation)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByHowto(Howto $howto, $offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp', 'h' ))
			->from($this->getEntityName(), 'p')
			->leftJoin('p.mainPicture', 'mp')
			->innerJoin('p.howtos', 'h')
			->where('h = :howto')
			->setParameter('howto', $howto)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public  function findGeocoded($filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'mp' ))
			->from($this->getEntityName(), 'p')
			->leftJoin('p.mainPicture', 'mp')
			->where('p.latitude IS NOT NULL')
			->andWhere('p.longitude IS NOT NULL')
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}