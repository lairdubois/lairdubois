<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\VotableParentInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\ScrapableInterface;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractKnowledge extends AbstractPublication implements VotableParentInterface, TitledInterface, PicturedInterface, IndexableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, ReportableInterface {

	const ATTRIB_TYPE = 0;
	const ATTRIB_MULTIPLE = 1;
	const ATTRIB_SUFFIX = 2;
	const ATTRIB_CHOICES = 3;
	const ATTRIB_USE_CHOICES_VALUE = 4;
	const ATTRIB_CONSTRAINTS = 5;
	const ATTRIB_DATA_CONSTRAINTS = 6;
	const ATTRIB_FILTER_QUERY = 7;
	const ATTRIB_LINKED_FIELDS = 8;
	const ATTRIB_MANDATORY = 9;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’ʼ#,.-]+$/", message="default.title.regex")
	 * @ladbAssert\UpperCaseRatio()
	 */
	private $title;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="main_picture_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Picture")
	 */
	private $mainPicture;

	/**
	 * @ORM\Column(type="integer", name="contributor_count")
	 */
	private $contributorCount = 0;

	/**
	 * @ORM\Column(type="integer", name="positive_vote_count")
	 */
	private $positiveVoteCount = 0;

	/**
	 * @ORM\Column(type="integer", name="negative_vote_count")
	 */
	private $negativeVoteCount = 0;

	/**
	 * @ORM\Column(type="integer", name="vote_count")
	 */
	private $voteCount = 0;

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

	/////

	private $isShown = true;

	/////

	// StrippedName /////

	public abstract function getStrippedName();

	// FieldDefs /////

	public abstract function getFieldDefs();

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

	// IsScrapable /////

	public function getIsScrapable() {
		return $this->getIsViewable();
	}

	// Title /////

	public function setTitle($title) {
		return $this->title = ucfirst($title);
	}

	public function getTitle() {
		return $this->title;
	}

	/////

	public function getBody() {
		return '';
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

	// MainPicture /////

	public function setMainPicture(\Ladb\CoreBundle\Entity\Picture $mainPicture = null) {
		$this->mainPicture = $mainPicture;
		return $this;
	}

	public function getMainPicture() {
		return $this->mainPicture;
	}

	// License /////

	public function getLicense() {
		return new \Ladb\CoreBundle\Entity\License(true, true);
	}

	// ContributorCount /////

	public function incrementContributorCount($by = 1) {
		return $this->contributorCount += intval($by);
	}

	public function setContributorCount($contributorCount) {
		$this->contributorCount = $contributorCount;
		return $this;
	}

	public function getContributorCount() {
		return $this->contributorCount;
	}

	// PositiveVoteCount /////

	public function incrementPositiveVoteCount($by = 1) {
		return $this->positiveVoteCount += intval($by);
	}

	public function setPositiveVoteCount($positiveVoteCount) {
		$this->positiveVoteCount = $positiveVoteCount;
		return $this;
	}

	public function getPositiveVoteCount() {
		return $this->positiveVoteCount;
	}

	// NegativeVoteCount /////

	public function incrementNegativeVoteCount($by = 1) {
		return $this->negativeVoteCount += intval($by);
	}

	public function setNegativeVoteCount($negativeVoteCount) {
		$this->negativeVoteCount = $negativeVoteCount;
		return $this;
	}

	public function getNegativeVoteCount() {
		return $this->negativeVoteCount;
	}

	// VoteCount /////

	public function incrementVoteCount($by = 1) {
		return $this->voteCount += intval($by);
	}

	public function setVoteCount($voteCount) {
		$this->voteCount = $voteCount;
		return $this;
	}

	public function getVoteCount() {
		return $this->voteCount;
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

}