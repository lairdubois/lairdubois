<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\AuthoredTrait;
use Ladb\CoreBundle\Model\BodiedTrait;
use Ladb\CoreBundle\Model\MultiPicturedTrait;
use Ladb\CoreBundle\Model\TypableInterface;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;

/**
 * @ORM\Table("tbl_core_comment")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\CommentRepository")
 */
class Comment implements TypableInterface, BodiedInterface, MultiPicturedInterface {

	use AuthoredTrait, BodiedTrait, MultiPicturedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Core\Comment';
	const TYPE = 1;

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
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=5000)
	 * @LadbAssert\NoMediaLink()
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $htmlBody;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_core_comment_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=0, max=4)
	 */
	protected $pictures;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Comment", inversedBy="children")
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $parent;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Comment", mappedBy="parent", cascade={"all"})
	 * @ORM\OrderBy({"createdAt" = "ASC"})
	 */
	private $children;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $childCount = 0;

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
		$this->children = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// Type /////

	public function getType() {
		return Comment::TYPE;
	}

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

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// Age /////

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	// UpdatedAt /////

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	// Parent /////

	public function setParent(\Ladb\CoreBundle\Entity\Core\Comment $parent = null) {
		$this->parent = $parent;
		return $this;
	}

	public function getParent() {
		return $this->parent;
	}

	// Children /////

	public function addChild(\Ladb\CoreBundle\Entity\Core\Comment $child) {
		if (!$this->children->contains($child)) {
			$this->children[] = $child;
			$child->setParent($this);
		}
		return $this;
	}

	public function removeChild(\Ladb\CoreBundle\Entity\Core\Comment $child) {
		if ($this->children->removeElement($child)) {
			$child->setParent(null);
		}
	}

	public function getChildren() {
		return $this->children;
	}

	public function resetChildren() {
		return $this->children->clear();
	}

	// ChildCount /////

	public function incrementChildCount($by = 1) {
		return $this->childCount += intval($by);
	}

	public function setChildCount($childCount) {
		$this->childCount = $childCount;
		return $this;
	}

	public function getChildCount() {
		return $this->childCount;
	}

}