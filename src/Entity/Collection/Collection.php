<?php

namespace App\Entity\Collection;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\CommentableInterface;
use App\Model\LikableInterface;
use App\Model\PicturedInterface;
use App\Model\PicturedTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
use App\Model\TitledInterface;
use App\Model\TitledTrait;
use App\Model\ViewableInterface;
use App\Model\ViewableTrait;
use App\Model\WatchableInterface;
use App\Model\WatchableTrait;
use App\Model\HtmlBodiedInterface;
use App\Model\HtmlBodiedTrait;
use App\Model\CommentableTrait;
use App\Model\IndexableInterface;
use App\Model\IndexableTrait;
use App\Model\LikableTrait;
use App\Entity\AbstractAuthoredPublication;
use App\Model\TaggableInterface;
use App\Model\TaggableTrait;

/**
 * @ORM\Table("tbl_collection")
 * @ORM\Entity(repositoryClass="App\Repository\Collection\CollectionRepository")
 */
class Collection extends AbstractAuthoredPublication implements IndexableInterface, TitledInterface, SluggedInterface, PicturedInterface, HtmlBodiedInterface, TaggableInterface, ViewableInterface, LikableInterface, CommentableInterface, CollectionnableInterface, WatchableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, HtmlBodiedTrait;
	use IndexableTrait, LikableTrait, WatchableTrait, CommentableTrait, TaggableTrait, ViewableTrait, CollectionnableTrait;

	const CLASS_NAME = 'App\Entity\Collection\Collection';
	const TYPE = 120;

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
	 * @ORM\JoinColumn(name="main_picture_id", nullable=true)
	 */
	private $mainPicture;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 * @Assert\Length(max=4000)
	 * @Assert\Length(min=5, groups={"public"})
	 * @Assert\NotBlank(groups={"public"})
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", name="html_body", nullable=true)
	 */
	private $htmlBody;

	/**
	 * @ORM\Column(type="integer", name="entry_count")
	 */
	private $entryCount = 0;

	/**
	 * @ORM\Column(type="array", name="entry_type_counters")
	 */
	private $entryTypeCounters;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Collection\Entry", mappedBy="collection", cascade={"all"})
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 */
	private $entries;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_collection_tag")
	 * @Assert\Count(min=2, groups={"public"})
	 */
	private $tags;

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
		$this->entries = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER;
	}

	// Type /////

	public function getType() {
		return Collection::TYPE;
	}

	// EntryTypeCounters /////

	public function incrementEntryTypeCounters($type, $by = 1) {
		if (!isset($this->entryTypeCounters[$type])) {
			$this->entryTypeCounters[$type] = 0;
		}
		$this->entryTypeCounters[$type] += intval($by);
		ksort ( $this->entryTypeCounters);
	}

	public function getEntryTypeCounters() {
		return $this->entryTypeCounters;
	}

	// EntryCount /////

	public function incrementEntryCount($by = 1) {
		return $this->entryCount += intval($by);
	}

	public function getEntryCount() {
		return $this->entryCount;
	}

	// Entries /////

	public function addEntry(\App\Entity\Collection\Entry $entry) {
		if (!$this->entries->contains($entry)) {
			$this->entries[] = $entry;
			$entry->setCollection($this);
		}
		return $this;
	}

	public function removeEntry(\App\Entity\Collection\Entry $entry) {
		if ($this->entries->removeElement($entry)) {
			$entry->setCollection(null);
		}
	}

	public function getEntries() {
		return $this->entries;
	}

	public function resetEntries() {
		$this->entries = new \Doctrine\Common\Collections\ArrayCollection();
	}

}