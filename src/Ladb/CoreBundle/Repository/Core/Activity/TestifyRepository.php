<?php

namespace Ladb\CoreBundle\Repository\Core\Activity;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class TestifyRepository extends AbstractEntityRepository {

	/////

	public function findByTestimonial(\Ladb\CoreBundle\Entity\Knowledge\School\Testimonial $testimonial) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 't' ))
			->from($this->getEntityName(), 't')
			->where('t.testimonial = :testimonial')
			->setParameter('testimonial', $testimonial)
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}