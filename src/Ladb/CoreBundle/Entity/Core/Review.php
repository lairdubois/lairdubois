<?php

namespace Ladb\CoreBundle\Entity\Core;

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
 * @ORM\Table("tbl_core_review",
 *		uniqueConstraints={
 *			@ORM\UniqueConstraint(name="ENTITY_USER_UNIQUE", columns={"entity_type", "entity_id", "user_id"})
 * 		},
 * 		indexes={
 *     		@ORM\Index(name="IDX_REVIEW_ENTITY", columns={"entity_type", "entity_id"})
 * 		})
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\ReviewRepository")
 */
class Review implements TypableInterface, TitledInterface, BodiedInterface {

	use AuthoredTrait, TitledTrait, BodiedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Core\Review';
	const TYPE = 4;
	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $createdAt;
	/**
	 * @ORM\Column(type="string", length=100, nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=100)
	 */
	protected $title;
	/**
	 * @ORM\Column(type="text", nullable=true)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=5, max=5000)
	 * @LadbAssert\NoMediaLink()
	 */
	protected $body;
	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;
	/**
	 * @ORM\Column(name="entity_type", type="smallint", nullable=false)
	 */
	private $entityType;
	/**
	 * @ORM\Column(name="entity_id", type="integer", nullable=false)
	 */
	private $entityId;
	/**
	 * @ORM\Column(name="updated_at", type="datetime")
	 * @Gedmo\Timestampable(on="update")
	 */
	private $updatedAt;
	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $rating;
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
		return self::TYPE;
	}

	// EntityType /////

	public function getEntityType() {
		return $this->entityType;
	}

	public function setEntityType($entityType) {
		$this->entityType = $entityType;
	}

	// EntityId /////

	public function getEntityId() {
		return $this->entityId;
	}

	public function setEntityId($entityId) {
		$this->entityId = $entityId;
		return $this;
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

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
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