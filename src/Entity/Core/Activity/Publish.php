<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_publish")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\PublishRepository")
 */
class Publish extends AbstractActivity {

	const STRIPPED_NAME = 'publish';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(name="publisher_user", nullable=true)
	 */
	protected $publisherUser;

	/**
	 * @ORM\Column(name="entity_type", type="smallint", nullable=false)
	 */
	private $entityType;

	/**
	 * @ORM\Column(name="entity_id", type="integer", nullable=false)
	 */
	private $entityId;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// PublisherUser /////

	public function setPublisherUser(\App\Entity\Core\User $user = null) {
		$this->publisherUser = $user;
		return $this;
	}

	public function getPublisherUser() {
		return is_null($this->publisherUser) ? $this->getUser() : $this->publisherUser;
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

}