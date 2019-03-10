<?php

namespace Ladb\CoreBundle\Entity\Collection;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\IdentifiableInterface;

/**
 * @ORM\Table("tbl_collection_entry",
 *		uniqueConstraints={
 *			@ORM\UniqueConstraint(name="ENTITY_COLLECTION_UNIQUE", columns={"entity_type", "entity_id", "collection_id"})
 * 		},
 * 		indexes={
 *     		@ORM\Index(name="IDX_COLLECTION_ENTRY_ENTITY", columns={"entity_type", "entity_id"})
 * 		})
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Collection\EntryRepository")
 */
class Entry implements IdentifiableInterface {

	const CLASS_NAME = 'LadbCoreBundle:Collection\Entry';

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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Collection\Collection")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $collection;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $createdAt;

	/**
	 * @ORM\Column(type="integer", name="sort_index")
	 */
	private $sortIndex = 0;

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

	// Collection /////

	public function setCollection(\Ladb\CoreBundle\Entity\Collection\Collection $collection = null) {
		$this->collection = $collection;
		return $this;
	}

	public function getCollection() {
		return $this->collection;
	}

	// SortIndex /////

	public function getSortIndex() {
		return $this->sortIndex;
	}

	public function setSortIndex($sortIndex) {
		$this->sortIndex = $sortIndex;
		return $this;
	}

}