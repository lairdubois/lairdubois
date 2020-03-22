<?php

namespace Ladb\CoreBundle\Repository\Core\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class FeedbackRepository extends AbstractEntityRepository {

	/////

	public function findByFeedback(\Ladb\CoreBundle\Entity\Core\Feedback $feedback) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from($this->getEntityName(), 'r')
			->where('r.feedback = :feedback')
			->setParameter('feedback', $feedback)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}