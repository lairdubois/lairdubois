<?php

namespace App\Manager\Knowledge\School;

use App\Entity\Knowledge\School\Testimonial;
use App\Manager\AbstractManager;
use App\Utils\ActivityUtils;

class TestimonialManager extends AbstractManager {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.ActivityUtils::class,
        ));
    }

	/////

	public function delete(Testimonial $testimonial, $flush = true) {

		$school = $testimonial->getSchool();

		// Decrement user testimonial count
		$testimonial->getUser()->getMeta()->incrementTestimonialCount(-1);

		// Decrement school testimonial count
		$school->incrementTestimonialCount(-1);

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->deleteActivitiesByTestimonial($testimonial, false);

		parent::deleteEntity($testimonial, $flush);
	}

}