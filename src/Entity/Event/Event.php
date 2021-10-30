<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Model\FeedbackableInterface;
use App\Model\FeedbackableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Entity\AbstractDraftableAuthoredPublication;
use App\Model\LocalisableInterface;
use App\Model\LocalisableTrait;
use App\Model\PicturedInterface;
use App\Model\PicturedTrait;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
use App\Model\JoinableTrait;
use App\Model\BlockBodiedInterface;
use App\Model\MultiPicturedTrait;
use App\Model\BlockBodiedTrait;
use App\Model\CommentableTrait;
use App\Model\IndexableTrait;
use App\Model\LikableTrait;
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
use App\Model\MultiPicturedInterface;
use App\Model\ViewableInterface;
use App\Model\LikableInterface;
use App\Model\WatchableInterface;
use App\Model\CommentableInterface;
use App\Model\ReportableInterface;
use App\Model\TaggableInterface;
use App\Model\ExplorableInterface;

/**
 * @ORM\Table("tbl_event")
 * @ORM\Entity(repositoryClass="App\Repository\Event\EventRepository")
 * @LadbAssert\BodyBlocks()
 * @ladbAssert\ValidEvent()
 */
class Event extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, MultiPicturedInterface, BlockBodiedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, ReportableInterface, ExplorableInterface, LocalisableInterface, JoinableInterface, FeedbackableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, MultiPicturedTrait, BlockBodiedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait, LocalisableTrait, FeedbackableTrait;
	use JoinableTrait {
		getIsJoinable as getIsJoinableTrait;
	}

	const TYPE = 123;

	const STATUS_UNKONW = 0;
	const STATUS_SCHEDULED = 1;
	const STATUS_RUNNING = 2;
	const STATUS_COMPLETED = 3;

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
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $body;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false, name="bodyExtract")
	 */
	private $bodyExtract;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_event_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true, onDelete="cascade")})
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
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_event_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5)
	 */
	private $pictures;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $location;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $latitude;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $longitude;

	/**
	 * @ORM\Column(name="start_at", type="datetime")
	 */
	private $startAt;

	/**
	 * @ORM\Column(name="start_date", type="date")
	 * @Assert\Date()
	 * @Assert\NotBlank()
	 */
	private $startDate;

	/**
	 * @ORM\Column(name="start_time", type="time", nullable=true)
	 * @Assert\Time()
	 */
	private $startTime;

	/**
	 * @ORM\Column(name="end_at", type="datetime", nullable=false)
	 */
	private $endAt;

	/**
	 * @ORM\Column(name="end_date", type="date", nullable=true)
	 * @Assert\Date()
	 */
	private $endDate;

	/**
	 * @ORM\Column(name="end_time", type="time", nullable=true)
	 * @Assert\Time()
	 */
	private $endTime;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 * @Assert\Url()
	 */
	private $url;

	/**
	 * @ORM\Column(name="online", type="boolean")
	 */
	private $online = false;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $cancelled = false;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_event_tag")
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

	/**
	 * @ORM\Column(type="integer", name="join_count")
	 */
	private $joinCount = 0;

	/**
	 * @ORM\Column(type="integer", name="feedback_count")
	 */
	private $feedbackCount = 0;

	/**
	 * @ORM\Column(type="boolean", options={"default":true})
	 */
	private $highlightable = true;

	/////

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER;
	}

	// Type /////

	public function getType() {
		return Event::TYPE;
	}

	// StartAt /////

	public function setStartAt($startAt) {
		$this->startAt = $startAt;
		return $this;
	}

	public function getStartAt() {
		return $this->startAt;
	}

	// StartDate /////

	public function setStartDate($startDate) {
		$this->startDate = $startDate;
		return $this;
	}

	public function getStartDate() {
		return $this->startDate;
	}

	// StartTime /////

	public function setStartTime($startTime) {
		$this->startTime = $startTime;
		return $this;
	}

	public function getStartTime() {
		return $this->startTime;
	}

	// EndAt /////

	public function setEndAt($endAt) {
		$this->endAt = $endAt;
		return $this;
	}

	public function getEndAt() {
		return $this->endAt;
	}

	// EndDate /////

	public function setEndDate($endDate) {
		$this->endDate = $endDate;
		return $this;
	}

	public function getEndDate() {
		return $this->endDate;
	}

	// EndTime /////

	public function setEndTime($endTime) {
		$this->endTime = $endTime;
		return $this;
	}

	public function getEndTime() {
		return $this->endTime;
	}

	// Duration /////

	public function getDuration() {
		if (is_null($this->getStartAt()) || is_null($this->getEndAt())) {
			return null;
		}
		return $this->getStartAt()->diff($this->getEndAt());
	}

	// Status /////

	public function getStatus() {
		$now = new \DateTime();
		if ($this->getStartAt() > $now) {
			return self::STATUS_SCHEDULED;
		} else if ($this->getStartAt() <= $now && $this->getEndAt() > $now) {
			return self::STATUS_RUNNING;
		} else if ($this->getEndAt() <= $now) {
			return self::STATUS_COMPLETED;
		}
		return self::STATUS_UNKONW;
	}

	// Url /////

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	public function getUrl() {
		return $this->url;
	}

	// Online /////

	public function setOnline($online) {
		$this->online = $online;
		return $this;
	}

	public function getOnline() {
		return $this->online;
	}

	// Cancelled /////

	public function setCancelled($cancelled) {
		$this->cancelled = $cancelled;
		return $this;
	}

	public function getCancelled() {
		return $this->cancelled;
	}

	// IsJoinable /////

	public function getIsJoinable() {
		return $this->getIsJoinableTrait()
			&& $this->getStatus() != Event::STATUS_COMPLETED
			&& !$this->getCancelled();
	}

	// Highlightable /////

	public function setHighlightable($highlightable) {
		$this->highlightable = $highlightable;
		return $this;
	}

	public function getHighlightable() {
		return $this->highlightable;
	}

}
