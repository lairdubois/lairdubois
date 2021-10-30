<?php

namespace App\Entity\Find;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Entity\Find\Content\Gallery;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Entity\AbstractDraftableAuthoredPublication;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
use App\Model\JoinableTrait;
use App\Model\BlockBodiedInterface;
use App\Model\BlockBodiedTrait;
use App\Model\CommentableTrait;
use App\Model\IndexableTrait;
use App\Model\LikableTrait;
use App\Model\PicturedTrait;
use App\Model\ScrapableTrait;
use App\Model\SitemapableInterface;
use App\Model\SitemapableTrait;
use App\Model\TaggableTrait;
use App\Model\TitledTrait;
use App\Model\ViewableTrait;
use App\Model\WatchableTrait;
use App\Model\JoinableInterface;
use App\Model\ScrapableInterface;
use App\Model\IndexableInterface;
use App\Model\TitledInterface;
use App\Model\PicturedInterface;
use App\Model\ViewableInterface;
use App\Model\LikableInterface;
use App\Model\WatchableInterface;
use App\Model\CommentableInterface;
use App\Model\ReportableInterface;
use App\Model\TaggableInterface;
use App\Model\ExplorableInterface;
use App\Entity\Find\Content\Event;

/**
 * @ORM\Table("tbl_find")
 * @ORM\Entity(repositoryClass="App\Repository\Find\FindRepository")
 * @LadbAssert\UniqueFind()
 * @LadbAssert\BodyBlocks()
 */
class Find extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, BlockBodiedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, ReportableInterface, ExplorableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, BlockBodiedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait;

	const TYPE = 104;

	const CONTENT_TYPE_NONE = 0;
	const CONTENT_TYPE_LINK = 1;
	const CONTENT_TYPE_GALLERY = 2;

	const KIND_NONE = 0;
	const KIND_WEBSITE = 1;
	const KIND_VIDEO = 2;
	const KIND_GALLERY = 3;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=4, max=100)
	 */
	private $title;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\Find\Content\AbstractContent", orphanRemoval=true, cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="content_id", nullable=false)
	 * @Assert\Valid
	 */
	private $content;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $kind = Find::KIND_NONE;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $body;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false, name="bodyExtract")
	 */
	private $bodyExtract;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_find_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true, onDelete="cascade")})
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

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=true, name="main_picture_id")
	 */
	private $mainPicture;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_find_tag")
	 */
	private $tags;

	/**
	 * @ORM\Column(type="integer", name="like_count")
	 */
	private $likeCount = 0;

	/**
	 * @ORM\Column(type="integer", name="watch_count")
	 */
	private $watchCount = 0;

	/**
	 * @ORM\Column(type="integer", name="comment_count")
	 */
	private $commentCount = 0;

	/**
	 * @ORM\Column(type="integer", name="private_collection_count")
	 */
	private $privateCollectionCount = 0;

	/**
	 * @ORM\Column(type="integer", name="public_collection_count")
	 */
	private $publicCollectionCount = 0;

	/**
	 * @ORM\Column(type="integer", name="view_count")
	 */
	private $viewCount = 0;

	/////

	/**
	 * @Assert\Range(min=1, max=3, minMessage="Vous devez sélectionner un type.")
	 */
	private $contentType = Find::CONTENT_TYPE_NONE;

	/////

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER;
	}

	// Type /////

	public function getType() {
		return Find::TYPE;
	}

	// ContentType /////

	public function getContentType() {
		return $this->contentType;
	}

	public function setContentType($contentType) {
		$this->contentType = $contentType;
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

	// Content /////

	public function getContent() {
		return $this->content;
	}

	public function setContent(\App\Entity\Find\Content\AbstractContent $content) {
		$this->content = $content;
		return $this;
	}

	// GeoPoint /////

	public function getGeoPoint() {
		if ($this->getContent() instanceof Gallery) {
			return $this->getContent()->getGeoPoint();
		}
		return null;
	}

}
