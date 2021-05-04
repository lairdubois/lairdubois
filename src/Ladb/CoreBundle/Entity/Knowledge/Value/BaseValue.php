<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\AuthoredTrait;
use Ladb\CoreBundle\Model\CommentableTrait;
use Ladb\CoreBundle\Model\VotableTrait;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\VotableInterface;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\WatchableChildInterface;

/**
 * @ORM\Table("tbl_knowledge2_value",
 *		uniqueConstraints={
 *			@ORM\UniqueConstraint(name="data_unique", columns={"data_hash", "parent_entity_type", "parent_entity_id", "parent_entity_field"})
 * 		})
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\BaseValueRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="integer")
 * @ORM\DiscriminatorMap({1 = "Integer", 2 = "Text", 3 = "Picture", 4 = "Url", 5 = "Location", 6 = "Phone", 7 = "Sign", 8 = "Longtext", 9 = "Language", 10 = "Isbn", 11 = "Price", 12 = "SoftwareIdentity", 13 = "FileExtension", 14 = "LinkableText", 15 = "Video", 16 = "BookIdentity", 17 = "Pdf", 18 = "Decimal"})
 * @LadbAssert\ValueSource()
 * @UniqueEntity(fields={"dataHash", "parentEntityType", "parentEntityId", "parentEntityField"})
 */
abstract class BaseValue implements AuthoredInterface, WatchableChildInterface, CommentableInterface, VotableInterface {

	use AuthoredTrait;
	use CommentableTrait, VotableTrait;

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
	 * @ORM\Column(name="parent_entity_type", type="smallint", nullable=false)
	 */
	protected $parentEntityType;

	/**
	 * @ORM\Column(name="parent_entity_id", type="integer", nullable=false)
	 */
	protected $parentEntityId;

	/**
	 * @ORM\Column(name="parent_entity_field", type="string", length=25, nullable=false)
	 */
	protected $parentEntityField;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(name="user_id", nullable=false)
	 */
	protected $user;

	protected $data;

	/**
	 * @ORM\Column(type="string", name="data_hash", length=32)
	 */
	protected $dataHash;

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
	 * @ORM\Column(name="updated_at", type="datetime")
	 * @Gedmo\Timestampable(on="update")
	 */
	private $updatedAt;

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
	 * @ORM\Column(name="moderation_score", type="integer")
	 */
	protected $moderationScore = 0;

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

	// CreatedAt /////

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// Age /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// UpdatedAt /////

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	// Legend /////

	public function getLegend() {
		return $this->legend;
	}

	public function setLegend($legend) {
		$this->legend = $legend;
		return $this;
	}

	// Source /////

	public function getSource() {
		return $this->source;
	}

	public function setSource($source) {
		$this->source = $source;
		return $this;
	}

	// SourceType /////

	public function getSourceType() {
		return $this->sourceType;
	}

	public function setSourceType($sourceType) {
		$this->sourceType = $sourceType;
		return $this;
	}

	// ModerationScore /////

	public function getModerationScore() {
		return $this->moderationScore;
	}

	public function setModerationScore($moderationScore) {
		$this->moderationScore = $moderationScore;
	}

	// Data /////

	public function getData() {
		return $this->data;
	}

	public function setData($data) {
		$this->data = $data;
		$this->dataHash = md5(serialize($data));
		return $this;
	}

	/////

	// IsDisplayGrid /////

	public function getIsDisplayGrid() {
		return false;
	}

}