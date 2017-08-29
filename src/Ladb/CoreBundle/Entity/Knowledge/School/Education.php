<?php

namespace Ladb\CoreBundle\Entity\Knowledge\School;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_knowledge2_school_education")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\School\EducationRepository")
 */
class Education {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\School\Education';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Knowledge\School", inversedBy="educations")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $school;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User", inversedBy="educations")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// School /////

	public function getSchool() {
		return $this->school;
	}

	public function setSchool(\Ladb\CoreBundle\Entity\Knowledge\School $school = null) {
		$this->school = $school;
		return $this;
	}

	// User /////

	public function getUser() {
		return $this->user;
	}

	public function setUser(\Ladb\CoreBundle\Entity\Core\User $user = null) {
		$this->user = $user;
		return $this;
	}

}