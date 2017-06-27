<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_witness")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\WitnessRepository")
 */
class Witness {

	const CLASS_NAME = 'LadbCoreBundle:Core\Witness';

	const KIND_NONE = 0;
	const KIND_UNPUBLISHED = 1;
	const KIND_CONVERTED = 2;
	const KIND_DELETED = 3;
	/**
	 * @ORM\Column(type="simple_array", nullable=true)
	 */
	protected $meta = null;
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
	 * @ORM\Column(name="entity_type", type="smallint", nullable=false)
	 */
	private $entityType;
	/**
	 * @ORM\Column(name="entity_id", type="integer", nullable=false)
	 */
	private $entityId;
	/**
	 * @ORM\Column(type="smallint", nullable=false)
	 */
	private $kind = self::KIND_NONE;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
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

	// Kind /////

	public function getKind() {
		return $this->kind;
	}

	public function setKind($kind) {
		$this->kind = $kind;
		return $this;
	}

	// Meta /////

	public function getMeta() {
		return $this->meta;
	}

	public function setMeta($meta) {
		$this->meta = $meta;
		return $this;
	}

}