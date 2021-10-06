<?php

namespace App\Manager\Knowledge;

use App\Entity\Knowledge\School;
use App\Manager\Knowledge\School\TestimonialManager;

class SchoolManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.knowledge_school_manager';

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), array(
            TestimonialManager::class => '?'.TestimonialManager::class,
        ));
    }

	public function delete(School $school, $withWitness = true, $flush = true) {

		// Unlink creations
		foreach ($school->getCreations() as $creation) {
			$creation->removeSchool($school);
		}

		// Unlink plans
		foreach ($school->getPlans() as $plan) {
			$plan->removeSchool($school);
		}

		// Unlink howtos
		foreach ($school->getHowtos() as $howto) {
			$howto->removeSchool($school);
		}

		// Delete testimonials
		$testimonialManager = $this->get(TestimonialManager::class);
		foreach ($school->getTestimonials() as $testimonial) {
			$testimonialManager->delete($testimonial);
		}

		parent::deleteKnowledge($school, $withWitness, $flush);
	}

}