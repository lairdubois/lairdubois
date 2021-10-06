<?php

namespace App\Repository\Core\Activity;

use App\Repository\AbstractEntityRepository;

class TestifyRepository extends AbstractEntityRepository {

	/////

	public function findByTestimonial(\App\Entity\Knowledge\School\Testimonial $testimonial) {
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