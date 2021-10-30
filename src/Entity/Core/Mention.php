<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Model\AuthoredInterface;
use App\Model\AuthoredTrait;

/**
 * @ORM\Table("tbl_core_mention",
 *		uniqueConstraints={
 *			@ORM\UniqueConstraint(name="ENTITY_MENTIONED_USER_UNIQUE", columns={"entity_type", "entity_id", "mentioned_user_id"})
 * 		},
 * 		indexes={
 *     		@ORM\Index(name="IDX_MENTION_ENTITY", columns={"entity_type", "entity_id"})
 * 		})
 * @ORM\Entity(repositoryClass="App\Repository\Core\MentionRepository")
 */
class Mention implements AuthoredInterface {

	use AuthoredTrait;

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
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(name="mentioned_user_id", nullable=false)
	 */
	private $mentionedUser = null;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

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

	// MentionedUser /////

	public function getMentionedUser() {
		return $this->mentionedUser;
	}

	public function setMentionedUser(\App\Entity\Core\User $mentionedUser = null) {
		$this->mentionedUser = $mentionedUser;
		return $this;
	}

}