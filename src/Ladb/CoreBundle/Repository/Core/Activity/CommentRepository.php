<?php

namespace Ladb\CoreBundle\Repository\Core\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class CommentRepository extends AbstractEntityRepository {

	/////

	public function findByComment(\Ladb\CoreBundle\Entity\Core\Comment $comment) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.comment = :comment')
			->setParameter('comment', $comment)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}