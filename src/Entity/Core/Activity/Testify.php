<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_core_activity_testify")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\TestifyRepository")
 */
class Testify extends AbstractActivity {

	const CLASS_NAME = 'App\Entity\Core\Activity\Testify';
	const STRIPPED_NAME = 'testify';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Knowledge\School\Testimonial")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $testimonial;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Testimonial /////

	public function setTestimonial(\App\Entity\Knowledge\School\Testimonial $testimonial) {
		$this->testimonial = $testimonial;
		return $this;
	}

	public function getTestimonial() {
		return $this->testimonial;
	}

}