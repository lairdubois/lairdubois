<?php

namespace Ladb\CoreBundle\Entity\Youtook;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\BodiedTrait;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\PicturedTrait;
use Ladb\CoreBundle\Model\ScrapableInterface;
use Ladb\CoreBundle\Model\ScrapableTrait;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\TitledTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Utils\VideoHostingUtils;
use Ladb\CoreBundle\Model\TypableInterface;

/**
 * @ORM\Table("tbl_youtook_took")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Youtook\TookRepository")
 * @LadbAssert\ValidTook()
 */
class Took extends AbstractAuthoredPublication implements TitledInterface, PicturedInterface, BodiedInterface, ScrapableInterface {

	use TitledTrait, PicturedTrait, BodiedTrait;
	use ScrapableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Youtook\Took';
	const TYPE = 112;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Url()
	 */
	private $url;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $kind = VideoHostingUtils::KIND_UNKNOW;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	private $embedIdentifier;

	/**
	 * @ORM\Column(type="string", length=255)
	 * @Assert\NotBlank()
	 */
	private $title;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\Column(type="text")
	 */
	private $body;

	/**
	 * @ORM\Column(type="text")
	 */
	private $htmlBody;

	/**
	 * @ORM\Column(type="string", length=255, name="thumbnail_loc")
	 */
	private $thumbnailUrl;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="main_picture_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Picture")
	 * @Assert\NotBlank()
	 */
	private $mainPicture;

	/**
	 * @ORM\Column(type="string", length=255, name="channel_id")
	 */
	private $channelId;

	/**
	 * @ORM\Column(type="string", length=255, name="channel_thumbnail_url")
	 */
	private $channelThumbnailUrl;

	/**
	 * @ORM\Column(type="string", length=255, name="channel_title")
	 */
	private $channelTitle;

	/////

	public function __construct() {
		$this->setIsDraft(false);
	}

	/////

	// Type /////

	public function getType() {
		return Took::TYPE;
	}

	// Url /////

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	public function getUrl() {
		return $this->url;
	}

	// Kind /////

	public function setKind($kind) {
		$this->kind = $kind;
		return $this;
	}

	public function getKind() {
		return $this->kind;
	}

	// EmbedIdentifier /////

	public function setEmbedIdentifier($embedIdentifier) {
		$this->embedIdentifier = $embedIdentifier;
		return $this;
	}

	public function getEmbedIdentifier() {
		return $this->embedIdentifier;
	}

	// Slug /////

	public function setSlug($slug) {
		$this->slug = $slug;
		return $this;
	}

	public function getSlug() {
		return $this->slug;
	}

	public function getSluggedId() {
		return $this->id.'-'.$this->slug;
	}

	// ThumbnailUrl /////

	public function setThumbnailUrl($thumbnailUrl) {
		$this->thumbnailUrl = $thumbnailUrl;
		return $this;
	}

	public function getThumbnailUrl() {
		return $this->thumbnailUrl;
	}

	// ChannelId /////

	public function setChannelId($channelId) {
		$this->channelId = $channelId;
		return $this;
	}

	public function getChannelId() {
		return $this->channelId;
	}

	// ChannelThumbnailUrl /////

	public function setChannelThumbnailUrl($channelThumbnailUrl) {
		$this->channelThumbnailUrl = $channelThumbnailUrl;
		return $this;
	}

	public function getChannelThumbnailUrl() {
		return $this->channelThumbnailUrl;
	}

	// ChannelTitle /////

	public function setChannelTitle($channelTitle) {
		$this->channelTitle = $channelTitle;
		return $this;
	}

	public function getChannelTitle() {
		return $this->channelTitle;
	}

}