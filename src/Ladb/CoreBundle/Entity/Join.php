<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_join")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\JoinRepository")
 */
class Join {

	const CLASS_NAME = 'LadbCoreBundle:Join';

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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

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

	// User /////

	public function setUser($user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

}