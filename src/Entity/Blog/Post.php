<?php

namespace App\Entity\Blog;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Entity\AbstractDraftableAuthoredPublication;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
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
use App\Model\IndexableInterface;
use App\Model\TitledInterface;
use App\Model\PicturedInterface;
use App\Model\BlockBodiedInterface;
use App\Model\ViewableInterface;
use App\Model\LikableInterface;
use App\Model\WatchableInterface;
use App\Model\CommentableInterface;
use App\Model\ReportableInterface;
use App\Model\TaggableInterface;
use App\Model\ExplorableInterface;
use App\Model\ScrapableInterface;

/**
 * @ORM\Table("tbl_blog_post")
 * @ORM\Entity(repositoryClass="App\Repository\Blog\PostRepository")
 * @LadbAssert\BodyBlocks()
 */
class Post extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, BlockBodiedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, ReportableInterface, ExplorableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, BlockBodiedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait;

	const CLASS_NAME = 'App\Entity\Blog\Post';
	const TYPE = 108;

	const HIGHLIGHT_LEVEL_NONE = 0;
	const HIGHLIGHT_LEVEL_USER_ONLY = 1;
	const HIGHLIGHT_LEVEL_ALL = 2;

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
	 * @ORM\JoinTable(name="tbl_blog_post_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true, onDelete="cascade")})
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
	 * @ORM\JoinColumn(nullable=false, name="main_picture_id")
	 * @Assert\NotBlank()
	 * @Assert\Type(type="App\Entity\Core\Picture")
	 */
	private $mainPicture;

	/**
	 * @ORM\Column(type="boolean", name="has_toc")
	 */
	private $hasToc = false;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_blog_post_tag")
	 */
	private $tags;

	/**
	 * @ORM\Column(type="smallint", name="highlight_level")
	 */
	private $highlightLevel = Post::HIGHLIGHT_LEVEL_NONE;

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

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER;
	}

	// Type /////

	public function getType() {
		return Post::TYPE;
	}

	// HasToc /////

	public function getHasToc() {
		return $this->hasToc;
	}

	public function setHasToc($hasToc) {
		$this->hasToc = $hasToc;
		return $this;
	}

	// HighlightLevel

	public function getHighlightLevel() {
		return $this->highlightLevel;
	}

	public function setHighlightLevel($highlightLevel) {
		$this->highlightLevel = $highlightLevel;
		return $this;
	}

}