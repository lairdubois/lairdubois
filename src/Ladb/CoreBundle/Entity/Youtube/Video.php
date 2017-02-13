<?php

namespace Ladb\CoreBundle\Entity\Youtube;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Utils\VideoHostingUtils;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Model\TypableInterface;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;

/**
 * @ORM\Table("tbl_youtube_video")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Youtube\VideoRepository")
 */
class Video {

	const CLASS_NAME = 'LadbCoreBundle:Youtube\Video';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	private $createdAt;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	private $embedIdentifier;

	/**
	 * @ORM\Column(type="string", length=255, name="thumbnail_loc")
	 */
	private $thumbnailLoc;

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

	/**
	 * @ORM\Column(type="text", name="channel_description")
	 */
	private $channelDescription;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// User /////

	public function setUser(\Ladb\CoreBundle\Entity\User $user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	// EmbedIdentifier /////

	public function getKind() {
		return VideoHostingUtils::KIND_YOUTUBE;
	}

	// EmbedIdentifier /////

	public function setEmbedIdentifier($embedIdentifier) {
		$this->embedIdentifier = $embedIdentifier;
		return $this;
	}

	public function getEmbedIdentifier() {
		return $this->embedIdentifier;
	}

	// ThumbnailLoc /////

	public function setThumbnailLoc($thumbnailLoc) {
		$this->thumbnailLoc = $thumbnailLoc;
	}

	public function getThumbnailLoc() {
		return $this->thumbnailLoc;
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

	// ChannelDescription /////

	public function setChannelDescription($channelDescription) {
		$this->channelDescription = $channelDescription;
	}

	public function getChannelDescription() {
		return $this->channelDescription;
	}

}