<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\School;
use Ladb\CoreBundle\Manager\Knowledge\School\TestimonialManager;

class SchoolManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.knowledge_school_manager';

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
		$testimonialManager = $this->get(TestimonialManager::NAME);
		foreach ($school->getTestimonials() as $testimonial) {
			$testimonialManager->delete($testimonial);
		}

		parent::deleteKnowledge($school, $withWitness, $flush);
	}

}