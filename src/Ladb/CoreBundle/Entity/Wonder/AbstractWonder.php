<?php

namespace Ladb\CoreBundle\Entity\Wonder;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\EmbeddableInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ExplorableInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\LicensedInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\TaggableInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractWonder extends AbstractAuthoredPublication implements IndexableInterface, TitledInterface, PicturedInterface, MultiPicturedInterface, LicensedInterface, TaggableInterface, ViewableInterface, LikableInterface, WatchableInterface, CommentableInterface, ReportableInterface, ExplorableInterface, EmbeddableInterface {

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=4)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’ʼ#,.:%-]+$/", message="default.title.regex")
	 * @ladbAssert\UpperCaseRatio()
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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=false, name="main_picture_id")
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Picture")
	 */
	private $mainPicture;

	/**
	 */
	protected $pictures;

	/**
	 */
	protected $tags;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\License", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, name="license_id")
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\License")
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
	 * @ORM\Column(type="integer", name="view_count")
	 */
	private $viewCount = 0;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="sticker_id", nullable=true)
	 */
	private $sticker;

	/**
	 */
	protected $referrals;

	/**
	 * @ORM\Column(type="integer", name="referral_count")
	 */
	private $referralCount = 0;

	/////

	private $isShown = true;

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

	// IsIndexable /////

	public function isIndexable() {
		return $this->isDraft !== true;
	}

	// IsViewable /////

	public function getIsViewable() {
		return $this->isDraft !== true;
	}

	// IsShown /////

	public function setIsShown($isShown) {
		$this->isShown = $isShown;
	}

	public function getIsShown() {
		return $this->isShown;
	}

	// Title /////

	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	public function getTitle() {
		return $this->title;
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

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

	// MainPicture /////

	public function setMainPicture(\Ladb\CoreBundle\Entity\Picture $mainPicture) {
		$this->mainPicture = $mainPicture;
		return $this;
	}

	public function getMainPicture() {
		return $this->mainPicture;
	}

	// Pictures /////

	public function addPicture(\Ladb\CoreBundle\Entity\Picture $picture) {
		if (!$this->pictures->contains($picture)) {
			$this->pictures[] = $picture;
		}
		return $this;
	}

	public function removePicture(\Ladb\CoreBundle\Entity\Picture $picture) {
		$this->pictures->removeElement($picture);
	}

	public function getPictures() {
		return $this->pictures;
	}

	public function resetPictures() {
		$this->pictures->clear();
	}

	public function getMaxPictureCount() {
		return 5;
	}

	// Tags /////

	public function addTag(\Ladb\CoreBundle\Entity\Tag $tag) {
		$this->tags[] = $tag;
		return $this;
	}

	public function removeTag(\Ladb\CoreBundle\Entity\Tag $tag) {
		$this->tags->removeElement($tag);
	}

	public function getTags() {
		return $this->tags;
	}

	// License /////

	public function setLicense($license) {
		$this->license = $license;
	}

	public function getLicense() {
		if (is_null($this->license)) {
			return new \Ladb\CoreBundle\Entity\License();
		}
		return $this->license;
	}

	// LikeCount /////

	public function incrementLikeCount($by = 1) {
		return $this->likeCount += intval($by);
	}

	public function setLikeCount($likeCount) {
		$this->likeCount = $likeCount;
		return $this;
	}

	public function getLikeCount() {
		return $this->likeCount;
	}

	// WatchCount /////

	public function incrementWatchCount($by = 1) {
		return $this->watchCount += intval($by);
	}

	public function setWatchCount($watchCount) {
		$this->watchCount = $watchCount;
		return $this;
	}

	public function getWatchCount() {
		return $this->watchCount;
	}

	// CommentCount /////

	public function incrementCommentCount($by = 1) {
		return $this->commentCount += intval($by);
	}

	public function setCommentCount($commentCount) {
		$this->commentCount = $commentCount;

		return $this;
	}

	public function getCommentCount() {
		return $this->commentCount;
	}

	// ViewCount /////

	public function incrementViewCount($by = 1) {
		return $this->viewCount += intval($by);
	}

	public function setViewCount($viewCount) {
		$this->viewCount = $viewCount;
		return $this;
	}

	public function getViewCount() {
		return $this->viewCount;
	}

	// Sticker /////

	public function setSticker(\Ladb\CoreBundle\Entity\Picture $sticker = null) {
		$this->sticker = $sticker;
		return $this;
	}

	public function getSticker() {
		return $this->sticker;
	}

	// Referrals /////

	public function addReferral(\Ladb\CoreBundle\Entity\Referer\Referral $referral) {
		if (!$this->referrals->contains($referral)) {
			$this->referrals[] = $referral;
			$this->referralCount = count($this->referrals);
			$referral->setEntityType($this->getType());
			$referral->setEntityId($this->getId());
		}
		return $this;
	}

	public function removeReferral(\Ladb\CoreBundle\Entity\Referer\Referral $referral) {
		$this->referrals->removeElement($referral);
		$referral->setEntityType(null);
		$referral->setEntityId(null);
	}

	public function getReferrals() {
		return $this->referrals;
	}

	// ReferralCount /////

	public function incrementReferralCount($by = 1) {
		$this->referralCount += intval($by);
	}

	public function getReferralCount() {
		return $this->referralCount;
	}

}