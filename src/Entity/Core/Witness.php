<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_witness", indexes={
 *     @ORM\Index(name="IDX_WITNESS_ENTITY", columns={"entity_type", "entity_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\Core\WitnessRepository")
 */
class Witness {

	const CLASS_NAME = 'App\Entity\Core\Witness';

	const KIND_NONE = 0;
	const KIND_UNPUBLISHED = 1;
	const KIND_CONVERTED = 2;
	const KIND_DELETED = 3;

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

	/**
	 * @ORM\Column(type="simple_array", nullable=true)
	 */
	protected $meta = null;

	/////

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

	// Kind /////

	public function setKind($kind) {
		$this->kind = $kind;
		return $this;
	}

	public function getKind() {
		return $this->kind;
	}

	// Meta /////

	public function setMeta($meta) {
		$this->meta = $meta;
		return $this;
	}

	public function getMeta() {
		return $this->meta;
	}

}