<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Model\AuthoredInterface;
use App\Model\BasicTimestampableInterface;
use App\Model\BasicTimestampableTrait;
use App\Model\MentionSourceInterface;
use App\Model\AuthoredTrait;
use App\Model\HtmlBodiedTrait;
use App\Model\MultiPicturedTrait;
use App\Model\TypableInterface;
use App\Model\HtmlBodiedInterface;
use App\Model\MultiPicturedInterface;

/**
 * @ORM\Table("tbl_core_comment", indexes={
 *     @ORM\Index(name="IDX_COMMENT_ENTITY", columns={"entity_type", "entity_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\Core\CommentRepository")
 */
class Comment implements TypableInterface, BasicTimestampableInterface, AuthoredInterface, HtmlBodiedInterface, MultiPicturedInterface, MentionSourceInterface {

	use BasicTimestampableTrait;
	use AuthoredTrait, HtmlBodiedTrait, MultiPicturedTrait;

	const CLASS_NAME = 'App\Entity\Core\Comment';
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
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=10000)
	 * @LadbAssert\NoMediaLink()
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false, name="htmlBody")
	 */
	private $htmlBody;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_core_comment_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=0, max=4)
	 */
	protected $pictures;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Comment", inversedBy="children")
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $parent;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Core\Comment", mappedBy="parent", cascade={"all"})
	 * @ORM\OrderBy({"createdAt" = "ASC"})
	 */
	private $children;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $childCount = 0;

	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\Core\Vote")
	 * @ORM\JoinColumn(name="vote_id", nullable=true)
	 */
	private $vote;

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

	// Parent /////

	public function setParent(\App\Entity\Core\Comment $parent = null) {
		$this->parent = $parent;
		return $this;
	}

	public function getParent() {
		return $this->parent;
	}

	// Children /////

	public function addChild(\App\Entity\Core\Comment $child) {
		if (!$this->children->contains($child)) {
			$this->children[] = $child;
			$child->setParent($this);
		}
		return $this;
	}

	public function removeChild(\App\Entity\Core\Comment $child) {
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

	// Vote /////

	public function setVote(\App\Entity\Core\Vote $vote = null) {
		$this->vote = $vote;
		return $this;
	}

	public function getVote() {
		return $this->vote;
	}

	// Title /////

	public function getTitle() {
		return mb_strimwidth($this->getBody(), 0, 50, '[...]');
	}

}