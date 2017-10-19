<?php

namespace Ladb\CoreBundle\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_core_activity_testify")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Activity\TestifyRepository")
 */
class Testify extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Core\Activity\Testify';
	const STRIPPED_NAME = 'testify';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Knowledge\School\Testimonial")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $testimonial;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Testimonial /////

	public function getTestimonial() {
		return $this->testimonial;
	}

	public function setTestimonial(\Ladb\CoreBundle\Entity\Knowledge\School\Testimonial $testimonial) {
		$this->testimonial = $testimonial;
		return $this;
	}

}