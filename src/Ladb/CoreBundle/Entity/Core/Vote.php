<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\AuthoredTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_vote",
 *		uniqueConstraints={
 *			@ORM\UniqueConstraint(name="ENTITY_USER_UNIQUE", columns={"entity_type", "entity_id", "user_id"})
 * 		},
 *		indexes={
 *			@ORM\Index(name="IDX_VOTE_ENTITY", columns={"entity_type", "entity_id"}),
 *			@ORM\Index(name="IDX_VOTE_PARENT_ENTITY", columns={"parent_entity_type", "parent_entity_id"})
 *		})
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\VoteRepository")
 */
class Vote implements AuthoredInterface {

	use AuthoredTrait;

	const CLASS_NAME = 'LadbCoreBundle:Core\Vote';

	const TYPE = 2;

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
	 * @ORM\Column(name="parent_entity_type", type="smallint", nullable=false)
	 */
	private $parentEntityType;

	/**
	 * @ORM\Column(name="parent_entity_id", type="integer", nullable=false)
	 */
	private $parentEntityId;

	/**
	 * @ORM\Column(name="parent_entity_field", type="string", length=40)
	 */
	private $parentEntityField;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	private $createdAt;

	/**
	 * @ORM\Column(name="updated_at", type="datetime")
	 * @Gedmo\Timestampable(on="update")
	 */
	private $updatedAt;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $user;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $score = 0;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Comment")
	 * @ORM\JoinColumn(name="comment_id", nullable=true)
	 */
	private $comment;

	/////

	// Id /////

	public function getId() {
		return $this->id;
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

	// ParentEntityType /////

	public function getParentEntityType() {
		return $this->parentEntityType;
	}

	public function setParentEntityType($parentEntityType) {
		$this->parentEntityType = $parentEntityType;
	}

	// ParentEntityId /////

	public function getParentEntityId() {
		return $this->parentEntityId;
	}

	public function setParentEntityId($parentEntityId) {
		$this->parentEntityId = $parentEntityId;
		return $this;
	}

	// ParentEntityField /////

	public function getParentEntityField() {
		return $this->parentEntityField;
	}

	public function setParentEntityField($groupName) {
		$this->parentEntityField = $groupName;
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

	// Score /////

	public function getScore() {
		return $this->score;
	}

	public function setScore($score) {
		$this->score = $score;
		return $this;
	}

	// Comment /////

	public function setComment(\Ladb\CoreBundle\Entity\Core\Comment $comment = null) {
		$this->comment = $comment;
		return $this;
	}

	public function getComment() {
		return $this->comment;
	}

}