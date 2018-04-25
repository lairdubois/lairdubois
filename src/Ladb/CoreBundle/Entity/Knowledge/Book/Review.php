<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Book;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\TypableInterface;
use Ladb\CoreBundle\Model\AuthoredTrait;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\TitledTrait;
use Ladb\CoreBundle\Model\BodiedTrait;
use Ladb\CoreBundle\Model\BodiedInterface;

/**
 * @ORM\Table("tbl_knowledge2_book_review")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Book\ReviewRepository")
 */
class Review implements TypableInterface, TitledInterface, BodiedInterface {

	use AuthoredTrait, TitledTrait, BodiedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Book\Review';
	const TYPE = 120;

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
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Book", inversedBy="reviews")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $book;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="string", length=100, nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=100)
	 */
	private $title;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $rating;

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
		return Review::TYPE;
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

	// Book /////

	public function getBook() {
		return $this->book;
	}

	public function setBook(\Ladb\CoreBundle\Entity\Knowledge\Book $book = null) {
		$this->book = $book;
		return $this;
	}

	// Rating /////

	public function getRating() {
		return $this->rating;
	}

	public function setRating($rating) {
		$this->rating = $rating;
		return $this;
	}

}