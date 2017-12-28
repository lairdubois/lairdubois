<?php

namespace Ladb\CoreBundle\Entity\Find;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Entity\AbstractDraftableAuthoredPublication;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\BlockBodiedTrait;
use Ladb\CoreBundle\Model\CommentableTrait;
use Ladb\CoreBundle\Model\IndexableTrait;
use Ladb\CoreBundle\Model\LikableTrait;
use Ladb\CoreBundle\Model\PicturedTrait;
use Ladb\CoreBundle\Model\ScrapableTrait;
use Ladb\CoreBundle\Model\SitemapableInterface;
use Ladb\CoreBundle\Model\SitemapableTrait;
use Ladb\CoreBundle\Model\TaggableTrait;
use Ladb\CoreBundle\Model\TitledTrait;
use Ladb\CoreBundle\Model\ViewableTrait;
use Ladb\CoreBundle\Model\WatchableTrait;
use Ladb\CoreBundle\Model\JoinableInterface;
use Ladb\CoreBundle\Model\ScrapableInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\TaggableInterface;
use Ladb\CoreBundle\Model\ExplorableInterface;
use Ladb\CoreBundle\Entity\Find\Content\Event;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;

/**
 * @ORM\Table("tbl_find")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Find\FindRepository")
 * @LadbAssert\UniqueFind()
 * @LadbAssert\BodyBlocks()
 */
class Find extends AbstractDraftableAuthoredPublication implements TitledInterface, PicturedInterface, BlockBodiedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, ReportableInterface, ExplorableInterface, JoinableInterface {

	use TitledTrait, PicturedTrait, BlockBodiedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Find\Find';
	const TYPE = 104;

	const CONTENT_TYPE_LINK = 0;
	const CONTENT_TYPE_GALLERY = 1;
	const CONTENT_TYPE_EVENT = 2;

	const KIND_NONE = 0;
	const KIND_WEBSITE = 1;
	const KIND_VIDEO = 2;
	const KIND_GALLERY = 3;
	const KIND_EVENT = 4;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=4)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’ʼ#,.:%?!-]+$/", message="default.title.regex")
	 * @ladbAssert\UpperCaseRatio()
	 */
	private $title;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\Find\Content\AbstractContent", orphanRemoval=true, cascade={"persist", "remove"})
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
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	private $bodyExtract;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_find_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true)})
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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=true, name="main_picture_id")
	 */
	private $mainPicture;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_find_tag")
	 * @Assert\Count(min=2)
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
	 * @ORM\Column(type="integer", name="view_count")
	 */
	private $viewCount = 0;

	/**
	 * @ORM\Column(type="integer", name="join_count")
	 */
	private $joinCount = 0;

	/////

	private $contentType = Find::CONTENT_TYPE_LINK;

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

	// Slug /////

	public function getSlug() {
		return $this->slug;
	}

	public function setSlug($slug) {
		$this->slug = $slug;
		return $this;
	}

	public function getSluggedId() {
		return $this->id.'-'.$this->slug;
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

	public function getIsJoinable() {
		return $this->getIsPublic()
			&& $this->getContent() instanceof Event
			&& $this->getContent()->getStatus() != Event::STATUS_COMPLETED;
	}

	public function getContent() {
		return $this->content;
	}

	// IsJoinable /////

	public function setContent(\Ladb\CoreBundle\Entity\Find\Content\AbstractContent $content) {
		$this->content = $content;
		return $this;
	}

	// JoinCount /////

	public function incrementJoinCount($by = 1) {
		return $this->joinCount += intval($by);
	}

	public function getJoinCount() {
		return $this->joinCount;
	}

	public function setJoinCount($joinCount) {
		$this->joinCount = $joinCount;
	}

}
