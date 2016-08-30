<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_spotlight")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\SpotlightRepository")
 */
class Spotlight {

	const CLASS_NAME = 'LadbCoreBundle:Spotlight';

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
	private $createdAt;

	/**
	 * @ORM\Column(name="finished_at", type="datetime", nullable=true)
	 */
	private $finishedAt;

	/**
	 * @ORM\Column(name="entity_type", type="smallint", nullable=false)
	 */
	private $entityType;

	/**
	 * @ORM\Column(name="entity_id", type="integer", nullable=false)
	 */
	private $entityId;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $enabled = true;

	// Id /////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// FinishedAt /////

	public function setFinishedAt($finishedAt) {
		$this->finishedAt = $finishedAt;
		return $this;
	}

	public function getFinishedAt() {
		return $this->finishedAt;
	}

	// Duration /////

	public function getDuration() {
		return $this->getCreatedAt()->diff($this->getFinishedAt());
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

	// Enabled /////

	public function setEnabled($enabled) {
		$this->enabled = $enabled;
	}

	public function getEnabled() {
		return $this->enabled;
	}

}