<?php

namespace App\Entity\Youtook;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Entity\AbstractAuthoredPublication;
use App\Model\HtmlBodiedInterface;
use App\Model\HtmlBodiedTrait;
use App\Model\PicturedInterface;
use App\Model\PicturedTrait;
use App\Model\ScrapableInterface;
use App\Model\ScrapableTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
use App\Model\TitledInterface;
use App\Model\TitledTrait;
use App\Utils\VideoHostingUtils;

/**
 * @ORM\Table("tbl_youtook_took")
 * @ORM\Entity(repositoryClass="App\Repository\Youtook\TookRepository")
 * @LadbAssert\ValidTook()
 */
class Took extends AbstractAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, HtmlBodiedInterface, ScrapableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, HtmlBodiedTrait;
	use ScrapableTrait;

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
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="main_picture_id", nullable=true)
	 * @Assert\Type(type="App\Entity\Core\Picture")
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

	// Type /////

	public function getType() {
		return Took::TYPE;
	}

	// Url /////

	public function getUrl() {
		return $this->url;
	}

	public function setUrl($url) {
		$this->url = $url;
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

	// EmbedIdentifier /////

	public function getEmbedIdentifier() {
		return $this->embedIdentifier;
	}

	public function setEmbedIdentifier($embedIdentifier) {
		$this->embedIdentifier = $embedIdentifier;
		return $this;
	}

	// ThumbnailUrl /////

	public function getThumbnailUrl() {
		return $this->thumbnailUrl;
	}

	public function setThumbnailUrl($thumbnailUrl) {
		$this->thumbnailUrl = $thumbnailUrl;
		return $this;
	}

	// ChannelId /////

	public function getChannelId() {
		return $this->channelId;
	}

	public function setChannelId($channelId) {
		$this->channelId = $channelId;
		return $this;
	}

	// ChannelThumbnailUrl /////

	public function getChannelThumbnailUrl() {
		return $this->channelThumbnailUrl;
	}

	public function setChannelThumbnailUrl($channelThumbnailUrl) {
		$this->channelThumbnailUrl = $channelThumbnailUrl;
		return $this;
	}

	// ChannelTitle /////

	public function getChannelTitle() {
		return $this->channelTitle;
	}

	public function setChannelTitle($channelTitle) {
		$this->channelTitle = $channelTitle;
		return $this;
	}

}