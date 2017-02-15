<?php

namespace Ladb\CoreBundle\Entity\Youtook;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Utils\VideoHostingUtils;
use Ladb\CoreBundle\Model\TypableInterface;

/**
 * @ORM\Table("tbl_youtook_took")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Youtook\TookRepository")
 * @LadbAssert\ValidTook()
 */
class Took extends AbstractAuthoredPublication implements TypableInterface{

	const CLASS_NAME = 'LadbCoreBundle:Youtook\Took';
	const TYPE = 112;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Url()
	 */
	private $url;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="thumbnail_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Picture")
	 */
	private $thumbnail;

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
	 */
	private $title;

	/**
	 * @ORM\Column(type="text", length=255)
	 */
	private $description;

	/**
	 * @ORM\Column(type="string", length=255, name="channel_id")
	 */
	private $channelId;

	/**
	 * @ORM\Column(type="string", length=255, name="channel_thumbnail_loc")
	 */
	private $channelThumbnailLoc;

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

	// Url /////

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	public function getUrl() {
		return $this->url;
	}

	// Thumbnail /////

	public function setThumbnail(\Ladb\CoreBundle\Entity\Picture $thumbnail = null) {
		$this->thumbnail = $thumbnail;
		return $this;
	}

	public function getThumbnail() {
		return $this->thumbnail;
	}

	public function getMainPicture() {
		return $this->getThumbnail();
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

	// Title /////

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getTitle() {
		return $this->title;
	}

	// Description /////

	public function setDescription($description) {
		$this->description = $description;
	}

	public function getDescription() {
		return $this->description;
	}

	// ChannelId /////

	public function setChannelId($channelId) {
		$this->channelId = $channelId;
	}

	public function getChannelId() {
		return $this->channelId;
	}

	// ChannelThumbnailLoc /////

	public function setChannelThumbnailLoc($channelThumbnailLoc) {
		$this->channelThumbnailLoc = $channelThumbnailLoc;
	}

	public function getChannelThumbnailLoc() {
		return $this->channelThumbnailLoc;
	}

	// ChannelTitle /////

	public function setChannelTitle($channelTitle) {
		$this->channelTitle = $channelTitle;
	}

	public function getChannelTitle() {
		return $this->channelTitle;
	}

}