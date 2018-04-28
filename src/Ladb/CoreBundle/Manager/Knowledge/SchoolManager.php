<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\School;
use Ladb\CoreBundle\Manager\Knowledge\School\TestimonialManager;

class SchoolManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.school_manager';

	public function delete(School $school, $withWitness = true, $flush = true) {

		// Delete testimonials
		$testimonialManager = $this->get(TestimonialManager::NAME);
		foreach ($school->getTestimonials() as $testimonial) {
			$testimonialManager->delete($testimonial);
		}

		parent::deleteKnowledge($school, $withWitness, $flush);
	}

}