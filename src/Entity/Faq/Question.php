<?php

namespace App\Entity\Faq;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Model\PicturedInterface;
use App\Model\PicturedTrait;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
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
use App\Model\ScrapableInterface;
use App\Model\ExplorableInterface;
use App\Model\IndexableInterface;
use App\Model\TitledInterface;
use App\Model\BlockBodiedInterface;
use App\Model\ViewableInterface;
use App\Model\LikableInterface;
use App\Model\WatchableInterface;
use App\Model\CommentableInterface;
use App\Model\ReportableInterface;
use App\Model\TaggableInterface;
use App\Entity\AbstractDraftableAuthoredPublication;

/**
 * @ORM\Table("tbl_faq_question")
 * @ORM\Entity(repositoryClass="App\Repository\Faq\QuestionRepository")
 * @LadbAssert\BodyBlocks()
 */
class Question extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, BlockBodiedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, ReportableInterface, ExplorableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, BlockBodiedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait;

	const CLASS_NAME = 'App\Entity\Faq\Question';
	const TYPE = 110;

	/**
	 * @ORM\Column(name="weight", type="integer")
	 */
	private $weight = 0;

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
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=true, name="main_picture_id")
	 * @Assert\Type(type="App\Entity\Core\Picture")
	 */
	private $mainPicture;

	/**
	 * @ORM\Column(type="string", length=50)
	 * @Assert\NotBlank()
	 */
	private $icon;

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
	 * @ORM\JoinTable(name="tbl_faq_question_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true, onDelete="cascade")})
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
	 * @ORM\Column(type="boolean", name="has_toc")
	 */
	private $hasToc = false;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_faq_question_tag")
	 * @Assert\Count(min=1)
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

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Type /////

	public function getType() {
		return Question::TYPE;
	}

	// Weight /////

	public function getWeight() {
		return $this->weight;
	}

	public function setWeight($weight) {
		$this->weight = $weight;
		return $this;
	}

	// Icon /////

	public function getIcon() {
		return $this->icon;
	}

	public function setIcon($icon) {
		$this->icon = $icon;
		return $this;
	}

	// HasToc /////

	public function getHasToc() {
		return $this->hasToc;
	}

	public function setHasToc($hasToc) {
		$this->hasToc = $hasToc;
		return $this;
	}

}