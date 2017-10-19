<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_tag_usage")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\TagUsageRepository")
 */
class TagUsage {

	const CLASS_NAME = 'LadbCoreBundle:Core\TagUsage';

	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Tag", inversedBy="usages")
	 */
	private $tag;

	/**
	 * @ORM\Id
	 * @ORM\Column(name="entity_type", type="smallint", nullable=false)
	 */
	private $entityType;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $score = 0;

	/**
	 * @ORM\Column(type="boolean", nullable=true)
	 */
	private $highlighted = false;

	/////

	// Tag /////

	public function setTag(\Ladb\CoreBundle\Entity\Core\Tag $tag) {
		$this->tag = $tag;
	}

	public function getTag() {
		return $this->tag;
	}

	// EntityType /////

	public function setEntityType($entityType) {
		$this->entityType = $entityType;
	}

	public function getEntityType() {
		return $this->entityType;
	}

	// Score /////

	public function incrementScore($by = 1) {
		return $this->score += intval($by);
	}

	public function getScore() {
		return $this->score;
	}

	// Highlighted /////

	public function setHighlighted($highlighted) {
		$this->highlighted = $highlighted;
		return $this;
	}

	public function getHighlighted() {
		return $this->highlighted;
	}

}