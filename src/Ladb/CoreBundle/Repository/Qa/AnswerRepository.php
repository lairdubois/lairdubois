<?php

namespace Ladb\CoreBundle\Repository\Qa;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class AnswerRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ) );
	}

	/////

	public function existsByQuestionAndUser(Question $question, User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(a.id)' ))
			->from($this->getEntityName(), 'a')
			->where('f.question = :question')
			->andWhere('f.user = :user')
			->setParameter('question', $question)
			->setParameter('user', $user)
		;

		try {
			return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
		} catch (NonUniqueResultException $e) {
			return false;
		}
	}

	/////

	public function findOneByIdJoinedOnUser($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a', 'u' ))
			->from($this->getEntityName(), 'a')
			->innerJoin('a.user', 'u')
			->where('a.id = :id')
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
			->select(array( 'a', 'u', 'bbs' ))
			->from($this->getEntityName(), 'a')
			->innerJoin('a.user', 'u')
			->innerJoin('a.bodyBlocks', 'bbs')
			->where('a.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByQuestion(Question $question, $sorter = 'score') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a', 'u', 'bbs' ))
			->from($this->getEntityName(), 'a')
			->innerJoin('a.question', 'q')
			->innerJoin('a.user', 'u')
			->innerJoin('a.bodyBlocks', 'bbs')
			->where('a.question = :question')
			->setParameter('question', $question)
		;

		if ('score' == $sorter) {
			$queryBuilder
				->addOrderBy('a.isBestAnswer', 'DESC')
				->addOrderBy('a.voteScore', 'DESC')
				->addOrderBy('a.createdAt', 'DESC')
			;
		} else if ('older' == $sorter) {
			$queryBuilder
				->addOrderBy('a.createdAt', 'ASC')
			;
		} else if ('recent' == $sorter) {
			$queryBuilder
				->addOrderBy('a.createdAt', 'DESC')
			;
		}

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findPaginedByUser(User $user, $offset, $limit) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a', 'u', 'q' ))
			->from($this->getEntityName(), 'a')
			->innerJoin('a.question', 'q')
			->innerJoin('a.user', 'u')
			->where('u = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
			->addOrderBy('a.createdAt', 'DESC')
		;

		return new Paginator($queryBuilder->getQuery());
	}

}