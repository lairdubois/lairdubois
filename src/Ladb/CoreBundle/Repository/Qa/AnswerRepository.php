<?php

namespace Ladb\CoreBundle\Repository\Qa;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Blog\Answer;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class AnswerRepository extends AbstractEntityRepository {

	public function createIsNotDraftQueryBuilder() {
		return $this->createQueryBuilder('a')->where('a.isDraft = false');	// FOSElasticaBundle bug -> use 'a'
	}

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ) );
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
			->select(array( 'p', 'u', 'bbs' ))
			->from($this->getEntityName(), 'p')
			->innerJoin('p.user', 'u')
			->innerJoin('p.bodyBlocks', 'bbs')
			->where('p.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}