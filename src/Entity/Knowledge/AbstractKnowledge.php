<?php

namespace App\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\CommentableTrait;
use App\Model\IndexableTrait;
use App\Model\LikableTrait;
use App\Model\PicturedTrait;
use App\Model\ScrapableTrait;
use App\Model\SitemapableInterface;
use App\Model\SitemapableTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
use App\Model\TitledTrait;
use App\Model\ViewableTrait;
use App\Model\VotableParentTrait;
use App\Model\WatchableTrait;
use App\Entity\AbstractPublication;
use App\Model\PicturedInterface;
use App\Model\CommentableInterface;
use App\Model\IndexableInterface;
use App\Model\LikableInterface;
use App\Model\ReportableInterface;
use App\Model\TitledInterface;
use App\Model\ViewableInterface;
use App\Model\VotableParentInterface;
use App\Model\WatchableInterface;
use App\Model\ScrapableInterface;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractKnowledge extends AbstractPublication implements TitledInterface, SluggedInterface, PicturedInterface, IndexableInterface, SitemapableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, ReportableInterface, VotableParentInterface {

	use TitledTrait, SluggedTrait, ScrapableTrait, PicturedTrait;
	use IndexableTrait, ViewableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait, VotableParentTrait;
	use SitemapableTrait { getIsSitemapable as public getIsSitemapableTrait; }

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
	const ATTRIB_QUALITY = 10;
	const ATTRIB_POST_PROCESSOR = 11;

	/**
	 * @ORM\Column(type="string", length=100)
	 */
	private $title;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="main_picture_id", nullable=true)
	 * @Assert\Type(type="App\Entity\Core\Picture")
	 */
	private $mainPicture;

	/**
	 * @ORM\Column(type="integer", name="completion_100")
	 */
	private $completion100 = 0;

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

	// StrippedName /////

	public abstract function getStrippedName();

	// FieldDefs /////

	public abstract function getFieldDefs();

	// IsRejected /////

	public function getIsRejected() {
		return false;
	}

	// IsSitemapable /////

	public function getIsSitemapable() {
		return !$this->getIsRejected() && $this->getIsSitemapableTrait();
	}

	/////

	public function getBody() {
		return '';
	}

	// License /////

	public function getLicense() {
		return new \App\Entity\Core\License(true, true);
	}

	// Completion100 /////

	public function setCompletion100($completion100) {
		$this->completion100 = $completion100;
		return $this;
	}

	public function getCompletion100() {
		return $this->completion100;
	}

	// ContributorCount /////

	public function incrementContributorCount($by = 1) {
		return $this->contributorCount += intval($by);
	}

	public function getContributorCount() {
		return $this->contributorCount;
	}

	public function setContributorCount($contributorCount) {
		$this->contributorCount = $contributorCount;
		return $this;
	}


}