<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\VotableInterface;
use Ladb\CoreBundle\Model\VotableParentInterface;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\WatchableChildInterface;

/**
 * @ORM\Table("tbl_knowledge2_value", uniqueConstraints={@ORM\UniqueConstraint(name="data_unique", columns={"data_hash", "parent_entity_type", "parent_entity_id", "parent_entity_field"})})
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\BaseValueRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="integer")
 * @ORM\DiscriminatorMap({1 = "Integer", 2 = "Text", 3 = "Picture", 4 = "Url", 5 = "Location", 6 = "Phone", 7 = "Sign", 8 = "Longtext"})
 * @LadbAssert\ValueSource()
 * @UniqueEntity(fields={"dataHash", "parentEntityType", "parentEntityId", "parentEntityField"})
 */
class BaseValue implements WatchableChildInterface, VotableInterface, CommentableInterface, AuthoredInterface {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\BaseValue';

	const SOURCE_TYPE_PERSONAL = 1;
	const SOURCE_TYPE_WEBSITE = 2;
	const SOURCE_TYPE_OTHER = 3;

	public static $SOURCE_TYPES = array(
		self::SOURCE_TYPE_PERSONAL => 'Connaissances personnelles',
		self::SOURCE_TYPE_WEBSITE => 'Site web',
		self::SOURCE_TYPE_OTHER => 'Autre',
	);

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="parent_entity_type", type="smallint", nullable=false)
	 */
	protected $parentEntityType;

	/**
	 * @ORM\Column(name="parent_entity_id", type="integer", nullable=false)
	 */
	protected $parentEntityId;

	/**
	 * @ORM\Column(name="parent_entity_field", type="string", length=20, nullable=false)
	 */
	protected $parentEntityField;

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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
	 * @ORM\JoinColumn(name="user_id", nullable=false)
	 */
	protected $user;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 * @Assert\Length(max=255)
	 */
	private $legend;

	/**
	 * @ORM\Column(type="smallint", name="source_type", nullable=true)
	 */
	private $sourceType;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 * @Assert\NotBlank(groups={"website", "other"})
	 * @Assert\Length(min=2, max=255, groups={"website", "other"})
	 * @Assert\Url(groups={"website"})
	 */
	private $source;

	/**
	 * @ORM\Column(type="integer", name="positive_vote_score")
	 */
	private $positiveVoteScore = 0;

	/**
	 * @ORM\Column(type="integer", name="negative_vote_score")
	 */
	private $negativeVoteScore = 0;

	/**
	 * @ORM\Column(type="integer", name="vote_score")
	 */
	private $voteScore = 0;

	/**
	 * @ORM\Column(type="integer", name="vote_count")
	 */
	private $voteCount = 0;

	/*
	 * Abstract
	 */
	protected $data;

	/**
	 * @ORM\Column(type="string", name="data_hash", length=32)
	 */
	protected $dataHash;

	/**
	 * @ORM\Column(type="integer", name="comment_count")
	 */
	private $commentCount = 0;

	/////

	// Type /////

	public function getType() {
		throw new \Exception("BaseValue->getType() need to be overrided to be used.");
	}

	// Id /////

	public function getId() {
		return $this->id;
	}

	// ParentEntity /////

	public function setParentEntity(VotableParentInterface $parentEntity) {
		$this->parentEntityType = $parentEntity->getType();
		$this->parentEntityId = $parentEntity->getId();
		return $this;
	}

	// ParentEntityType /////

	public function setParentEntityType($parentEntityType) {
		$this->parentEntityType = $parentEntityType;
	}

	public function getParentEntityType() {
		return $this->parentEntityType;
	}

	// ParentEntityId /////

	public function setParentEntityId($parentEntityId) {
		$this->parentEntityId = $parentEntityId;
	}

	public function getParentEntityId() {
		return $this->parentEntityId;
	}

	// ParentEntityField /////

	public function setParentEntityField($parentEntityField) {
		$this->parentEntityField = $parentEntityField;
		return $this;
	}

	public function getParentEntityField() {
		return $this->parentEntityField;
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

	// User /////

	public function setUser(\Ladb\CoreBundle\Entity\User $user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	// Legend /////

	public function setLegend($legend) {
		$this->legend = $legend;
		return $this;
	}

	public function getLegend() {
		return $this->legend;
	}

	// Source /////

	public function setSource($source) {
		$this->source = $source;
		return $this;
	}

	public function getSource() {
		return $this->source;
	}

	// SourceType /////

	public function setSourceType($sourceType) {
		$this->sourceType = $sourceType;
		return $this;
	}

	public function getSourceType() {
		return $this->sourceType;
	}

	// PositiveVoteScore /////

	public function incrementPositiveVoteScore($by = 1) {
		return $this->positiveVoteScore += intval($by);
	}

	public function setPositiveVoteScore($positiveVoteScore) {
		return $this->positiveVoteScore = $positiveVoteScore;
	}

	public function getPositiveVoteScore() {
		return $this->positiveVoteScore;
	}

	// NegativeVoteScore /////

	public function incrementNegativeVoteScore($by = 1) {
		return $this->negativeVoteScore += intval($by);
	}

	public function setNegativeVoteScore($negativeVoteScore) {
		return $this->negativeVoteScore = $negativeVoteScore;
	}

	public function getNegativeVoteScore() {
		return $this->negativeVoteScore;
	}

	// voteScore /////

	public function incrementVoteScore($by = 1) {
		return $this->voteScore += intval($by);
	}

	public function setVoteScore($voteScore) {
		return $this->voteScore = $voteScore;
	}

	public function getVoteScore() {
		return $this->voteScore;
	}

	// voteCount /////

	public function incrementVoteCount($by = 1) {
		return $this->voteCount += intval($by);
	}

	public function setVoteCount($voteCount) {
		return $this->voteCount = $voteCount;
	}

	public function getVoteCount() {
		return $this->voteCount;
	}

	// Data /////

	public function setData($data) {
		$this->data = $data;
		$this->dataHash = md5(serialize($data));
		return $this;
	}

	public function getData() {
		return $this->data;
	}

	// CommentCount /////

	public function incrementCommentCount($by = 1) {
		return $this->commentCount += intval($by);
	}

	public function setCommentCount($commentCount) {
		$this->commentCount = $commentCount;
		return $this;
	}

	public function getCommentCount() {
		return $this->commentCount;
	}

}