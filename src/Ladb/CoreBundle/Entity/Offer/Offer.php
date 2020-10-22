<?php

namespace Ladb\CoreBundle\Entity\Offer;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Model\CollectionnableTrait;
use Ladb\CoreBundle\Model\RepublishableInterface;
use Ladb\CoreBundle\Model\RepublishableTrait;
use Ladb\CoreBundle\Model\LocalisableExtendedInterface;
use Ladb\CoreBundle\Model\LocalisableExtendedTrait;
use Ladb\CoreBundle\Model\LocalisableInterface;
use Ladb\CoreBundle\Model\LocalisableTrait;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\MultiPicturedTrait;
use Ladb\CoreBundle\Model\SluggedInterface;
use Ladb\CoreBundle\Model\SluggedTrait;
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
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\TaggableInterface;
use Ladb\CoreBundle\Model\ExplorableInterface;
use Ladb\CoreBundle\Entity\Find\Content\Event;

/**
 * @ORM\Table("tbl_offer")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Offer\OfferRepository")
 * @LadbAssert\BodyBlocks()
 */
class Offer extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, MultiPicturedInterface, BlockBodiedInterface, RepublishableInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, ReportableInterface, ExplorableInterface, LocalisableInterface, LocalisableExtendedInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, MultiPicturedTrait, BlockBodiedTrait;
	use RepublishableTrait, IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait, LocalisableTrait, LocalisableExtendedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Offer\Offer';
	const TYPE = 122;

	const ACTIVE_LIFETIME = '30 day';
	const FULL_LIFETIME = '60 day';
	const MAX_PUBLISH_COUNT = 5;

	const KIND_NONE = 0;
	const KIND_OFFER = 1;
	const KIND_REQUEST = 2;

	const CATEGORY_NONE = 0;
	const CATEGORY_OTHER = 1;
	const CATEGORY_JOB = 2;
	const CATEGORY_TOOL = 3;
	const CATEGORY_MATERIAL = 4;
	const CATEGORY_SERVICE = 5;

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
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	private $bodyExtract;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_offer_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true, onDelete="cascade")})
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
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_offer_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5, groups={"offer"})
	 * @Assert\Count(min=0, max=5, groups={"request"})
	 */
	protected $pictures;

	/**
	 * @ORM\Column(type="smallint")
	 * @Assert\NotBlank(message="Vous devez définir un type.")
	 * @Assert\Choice(callback="getKinds", message="Ce type est invalide.")
	 */
	private $kind = self::KIND_NONE;

	/**
	 * @ORM\Column(type="smallint")
	 * @Assert\NotBlank(message="Vous devez définir une catégorie.")
	 * @Assert\Choice(callback="getCategories", message="Cette catégorie est invalide.")
	 */
	private $category = self::CATEGORY_NONE;

	/**
	 * @ORM\Column(type="string", length=20)
	 */
	protected $price;

	/**
	 * @ORM\Column(type="integer", name="raw_price")
	 * @Assert\GreaterThanOrEqual(0)
	 */
	protected $rawPrice = 0;

	/**
	 * @ORM\Column(type="string", length=3)
	 */
	protected $currency = 'EUR';

	/**
	 * @ORM\Column(type="string", length=10, name="price_suffix", nullable=true)
	 * @Assert\Length(max=10)
	 */
	protected $priceSuffix;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 * @Assert\Length(max=100)
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
	 * @ORM\Column(type="string", name="postal_code", nullable=true)
	 */
	private $postalCode;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $locality;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $country;

	/**
	 * @ORM\Column(type="string", name="geographical_areas", nullable=true)
	 */
	private $geographicalAreas;

	/**
	 * @ORM\Column(type="string", name="formatted_adress", nullable=true)
	 */
	private $formattedAdress;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_offer_tag")
	 * @Assert\Count(min=2)
	 */
	private $tags;

	/**
	 * @ORM\Column(type="integer", name="publish_count")
	 */
	private $publishCount = 0;

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
		return Offer::TYPE;
	}

	// Date /////

	public function getExpiredDate() {
		return (clone $this->getChangedAt())->modify('+'.self::ACTIVE_LIFETIME);
	}

	public function getOudatedDate() {
		return (clone $this->getChangedAt())->modify('+'.self::FULL_LIFETIME);
	}

	// Pictures /////

	public function getMaxPictureCount() {
		return 5;
	}

	// Kind /////

	public function setKind($kind) {
		$this->kind = $kind;
		return $this;
	}

	public function getKind() {
		return $this->kind;
	}

	public function isKindOffer() {
		return $this->getKind() == self::KIND_OFFER;
	}

	public function isKindRequest() {
		return $this->getKind() == self::KIND_REQUEST;
	}

	public function getKinds() {
		return array( self::KIND_OFFER, self::KIND_REQUEST );
	}

	// Category /////

	public function setCategory($category) {
		$this->category = $category;
		return $this;
	}

	public function getCategory() {
		return $this->category;
	}

	public function isCategoryOther() {
		return $this->getCategory() == self::CATEGORY_OTHER;
	}

	public function isCategoryJob() {
		return $this->getCategory() == self::CATEGORY_JOB;
	}

	public function isCategoryTool() {
		return $this->getCategory() == self::CATEGORY_TOOL;
	}

	public function isCategoryMaterial() {
		return $this->getCategory() == self::CATEGORY_MATERIAL;
	}

	public function isCategoryService() {
		return $this->getCategory() == self::CATEGORY_SERVICE;
	}

	public function getCategories() {
		return array( self::CATEGORY_JOB, self::CATEGORY_TOOL, self::CATEGORY_MATERIAL, self::CATEGORY_SERVICE, self::CATEGORY_OTHER );
	}

	// Price /////

	public function setPrice($price) {
		$this->price = $price;
		return $this;
	}

	public function getPrice() {
		return $this->price;
	}

	// RawPrice /////

	public function setRawPrice($rawPrice) {
		$this->rawPrice = $rawPrice;
		return $this;
	}

	public function getRawPrice() {
		return $this->rawPrice;
	}

	// Currency /////

	public function setCurrency($currency) {
		$this->currency = $currency;
		return $this;
	}

	public function getCurrency() {
		return $this->currency;
	}

	// PriceSuffix /////

	public function setPriceSuffix($priceSuffix) {
		$this->priceSuffix = $priceSuffix;
		return $this;
	}

	public function getPriceSuffix() {
		return $this->priceSuffix;
	}

	// isExpired /////

	public function isExpired() {
		return $this->getIsDraft() && $this->getPublishCount() > 0;
	}

}
