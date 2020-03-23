<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\BlockBodiedTrait;
use Ladb\CoreBundle\Model\MentionSourceInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\TypableInterface;
use Ladb\CoreBundle\Model\AuthoredTrait;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\TitledTrait;
use Ladb\CoreBundle\Model\HtmlBodiedTrait;
use Ladb\CoreBundle\Model\HtmlBodiedInterface;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\BasicTimestampableInterface;
use Ladb\CoreBundle\Model\BasicTimestampableTrait;

/**
 * @ORM\Table("tbl_core_feedback",
 * 		indexes={
 *     		@ORM\Index(name="IDX_FEEDBACK_ENTITY", columns={"entity_type", "entity_id"})
 * 		})
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\FeedbackRepository")
 */
class Feedback implements TypableInterface, BasicTimestampableInterface, AuthoredInterface, TitledInterface, BlockBodiedInterface, MentionSourceInterface {

	use BasicTimestampableTrait;
	use AuthoredTrait, TitledTrait, BlockBodiedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Core\Feedback';
	const TYPE = 6;

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
	protected $createdAt;

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
	 * @ORM\Column(type="string", length=100, nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=100)
	 */
	protected $title;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	protected $body;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	private $bodyExtract;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_core_feedback_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true, onDelete="cascade")})
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1)
	 */
	private $bodyBlocks;

	/**
	 * @ORM\Column(type="integer", name="body_block_picture_count")
	 */
	private $bodyBlockPictureCount = 0;

	/**
	 * @ORM\Column(type="integer", name="body_block_video_count")
	 */
	private $bodyBlockVideoCount = 0;

	/////

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Type /////

	public function getType() {
		return self::TYPE;
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

}