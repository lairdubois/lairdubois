<?php

namespace App\Entity\Promotion;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Entity\AbstractDraftableAuthoredPublication;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
use App\Model\LicensedTrait;
use App\Model\HtmlBodiedInterface;
use App\Model\HtmlBodiedTrait;
use App\Model\PicturedInterface;
use App\Model\PicturedTrait;
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
use App\Model\IndexableInterface;
use App\Model\TitledInterface;
use App\Model\ViewableInterface;
use App\Model\LikableInterface;
use App\Model\WatchableInterface;
use App\Model\CommentableInterface;
use App\Model\ReportableInterface;
use App\Model\TaggableInterface;
use App\Model\ExplorableInterface;
use App\Model\ScrapableInterface;

/**
 * @ORM\Table("tbl_promotion_graphic")
 * @ORM\Entity(repositoryClass="App\Repository\Promotion\GraphicRepository")
 * @LadbAssert\BodyBlocks()
 */
class Graphic extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, HtmlBodiedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, ReportableInterface, ExplorableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, HtmlBodiedTrait, LicensedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait;

	const CLASS_NAME = 'App\Entity\Promotion\Graphic';
	const TYPE = 117;

	const ACCEPTED_FILE_TYPE = '/(\.|\/)(pdf|svg)$/i';

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
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false, name="htmlBody")
	 */
	private $htmlBody;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_promotion_graphic_tag")
	 */
	private $tags;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Resource", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=false, name="resource_id")
	 * @Assert\Type(type="App\Entity\Core\Resource")
	 * @Assert\NotNull()
	 */
	private $resource;

	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\Core\License", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, name="license_id")
	 * @Assert\Type(type="App\Entity\Core\License")
	 */
	private $license;

	/**
	 * @ORM\Column(type="integer", name="zip_archive_size")
	 */
	private $zipArchiveSize = 0;

	/**
	 * @ORM\Column(type="integer", name="download_count")
	 */
	private $downloadCount = 0;

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
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER;
	}

	// Type /////

	public function getType() {
		return Graphic::TYPE;
	}

	// Resources /////

	public function setResource(\App\Entity\Core\Resource $resource) {
		$this->resource = $resource;
		return $this;
	}

	public function getResource() {
		return $this->resource;
	}

	// ResourceSizeSum /////

	public function setZipArchiveSize($zipArchiveSize) {
		return $this->zipArchiveSize = $zipArchiveSize;
	}

	public function getZipArchiveSize() {
		return $this->zipArchiveSize;
	}

	// DownloadCount /////

	public function incrementDownloadCount($by = 1) {
		return $this->downloadCount += intval($by);
	}

	public function setDownloadCount($downloadCount) {
		$this->downloadCount = $downloadCount;
		return $this;
	}

	public function getDownloadCount() {
		return $this->downloadCount;
	}

}