<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_vote")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\VoteRepository")
 */
class Vote {

	const CLASS_NAME = 'LadbCoreBundle:Vote';

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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $user;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $score = 0;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// EntityType /////

	public function setEntityType($entityType) {
		$this->entityType = $entityType;
	}

	public function getEntityType() {
		return $this->entityType;
	}

	// EntityId /////

	public function setEntityId($entityId) {
		$this->entityId = $entityId;
		return $this;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	// ParentEntityType /////

	public function setParentEntityType($parentEntityType) {
		$this->parentEntityType = $parentEntityType;
	}

	public function getParentEntityType() {
		return $this->parentEntityType;
	}

	// ParentEntityId /////

	public function setParentEntityId($parentEntityId) {
		$this->parentEntityId = $parentEntityId;
		return $this;
	}

	public function getParentEntityId() {
		return $this->parentEntityId;
	}

	// ParentEntityField /////

	public function setParentEntityField($groupName) {
		$this->parentEntityField = $groupName;
	}

	public function getParentEntityField() {
		return $this->parentEntityField;
	}

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// UpdatedAt /////

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	// User /////

	public function setUser(\Ladb\CoreBundle\Entity\User $user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	// Score /////

	public function setScore($score) {
		$this->score = $score;
		return $this;
	}

	public function getScore() {
		return $this->score;
	}

}