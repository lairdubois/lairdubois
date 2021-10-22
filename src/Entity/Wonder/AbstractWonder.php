<?php

namespace App\Entity\Wonder;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Model\StripableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Model\LinkedToInterface;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
use App\Model\CommentableTrait;
use App\Model\EmbeddableTrait;
use App\Model\IndexableTrait;
use App\Model\LicensedTrait;
use App\Model\LikableTrait;
use App\Model\MultiPicturedTrait;
use App\Model\PicturedTrait;
use App\Model\ScrapableTrait;
use App\Model\SitemapableInterface;
use App\Model\SitemapableTrait;
use App\Model\TaggableTrait;
use App\Model\TitledTrait;
use App\Model\ViewableTrait;
use App\Model\WatchableTrait;
use App\Model\EmbeddableInterface;
use App\Model\CommentableInterface;
use App\Model\ExplorableInterface;
use App\Model\IndexableInterface;
use App\Model\LicensedInterface;
use App\Model\LikableInterface;
use App\Model\ReportableInterface;
use App\Model\TaggableInterface;
use App\Model\TitledInterface;
use App\Model\PicturedInterface;
use App\Model\MultiPicturedInterface;
use App\Model\ViewableInterface;
use App\Model\WatchableInterface;
use App\Model\ScrapableInterface;
use App\Model\StripableInterface;
use App\Entity\AbstractDraftableAuthoredPublication;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractWonder extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, MultiPicturedInterface, LicensedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, ReportableInterface, ExplorableInterface, EmbeddableInterface, StripableInterface, LinkedToInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, MultiPicturedTrait, LicensedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CollectionnableTrait, CommentableTrait, EmbeddableTrait, StripableTrait;

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
	protected $body;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=false, name="main_picture_id")
	 * @Assert\Type(type="App\Entity\Core\Picture")
	 */
	private $mainPicture;

	/**
	 */
	protected $pictures;

	/**
	 */
	protected $tags;

	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\Core\License", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, name="license_id")
	 * @Assert\Type(type="App\Entity\Core\License")
	 */
	private $license;

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
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="sticker_id", nullable=true)
	 */
	private $sticker;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="strip_id", nullable=true)
	 */
	private $strip;

	/**
	 */
	protected $referrals;

	/**
	 * @ORM\Column(type="integer", name="referral_count")
	 */
	private $referralCount = 0;

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
		$this->referrals = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER;
	}

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

	// Pictures /////

	public function getMaxPictureCount() {
		return 5;
	}

}