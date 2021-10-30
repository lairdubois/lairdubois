<?php

namespace App\Entity\Knowledge\School;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Model\TypableInterface;
use App\Model\AuthoredTrait;
use App\Model\HtmlBodiedTrait;
use App\Model\HtmlBodiedInterface;

/**
 * @ORM\Table("tbl_knowledge2_school_testimonial")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\School\TestimonialRepository")
 * @LadbAssert\SchoolTestimonial()
 */
class Testimonial implements TypableInterface, HtmlBodiedInterface {

	use AuthoredTrait, HtmlBodiedTrait;

	const TYPE = 116;

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $createdAt;

	/**
	 * @ORM\Column(name="updated_at", type="datetime")
	 * @Gedmo\Timestampable(on="update")
	 */
	private $updatedAt;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Knowledge\School", inversedBy="testimonials")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $school;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $diploma;

	/**
	 * @ORM\Column(name="from_year", type="integer")
	 * @Assert\NotNull()
	 * @Assert\GreaterThanOrEqual(1900)
	 */
	private $fromYear;

	/**
	 * @ORM\Column(name="to_year", type="integer", nullable=true)
	 */
	private $toYear;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=5, max=5000)
	 * @LadbAssert\NoMediaLink()
	 */
	private $body;

	/**
	 * @ORM\Column(name="html_body", type="text", nullable=true)
	 */
	private $htmlBody;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Type /////

	public function getType() {
		return Testimonial::TYPE;
	}

	// CreatedAt /////

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// UpdatedAt /////

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	// School /////

	public function getSchool() {
		return $this->school;
	}

	public function setSchool(\App\Entity\Knowledge\School $school = null) {
		$this->school = $school;
		return $this;
	}

	// Diploma /////

	public function getDiploma() {
		return $this->diploma;
	}

	public function setDiploma($diploma) {
		$this->diploma = $diploma;
		return $this;
	}

	// FromYear /////

	public function getFromYear() {
		return $this->fromYear;
	}

	public function setFromYear($fromYear) {
		$this->fromYear = $fromYear;
		return $this;
	}

	// ToYear /////

	public function getToYear() {
		return $this->toYear;
	}

	public function setToYear($toYear) {
		$this->toYear = $toYear;
		return $this;
	}

}