<?php

namespace Ladb\CoreBundle\Entity\Knowledge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Model\CollectionnableTrait;
use Ladb\CoreBundle\Model\CommentableTrait;
use Ladb\CoreBundle\Model\IndexableTrait;
use Ladb\CoreBundle\Model\LikableTrait;
use Ladb\CoreBundle\Model\PicturedTrait;
use Ladb\CoreBundle\Model\ScrapableTrait;
use Ladb\CoreBundle\Model\SitemapableInterface;
use Ladb\CoreBundle\Model\SitemapableTrait;
use Ladb\CoreBundle\Model\SluggedInterface;
use Ladb\CoreBundle\Model\SluggedTrait;
use Ladb\CoreBundle\Model\TitledTrait;
use Ladb\CoreBundle\Model\ViewableTrait;
use Ladb\CoreBundle\Model\VotableParentTrait;
use Ladb\CoreBundle\Model\WatchableTrait;
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
	const ATTRIB_POST_PROCESSOR = 10;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’ʼ#,.:-]+$/", message="default.title.regex")
	 * @ladbAssert\UpperCaseRatio()
	 */
	private $title;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="main_picture_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
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
	 * @ORM\Column(type="integer", name="collection_count")
	 */
	private $collectionCount = 0;

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
		return new \Ladb\CoreBundle\Entity\Core\License(true, true);
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