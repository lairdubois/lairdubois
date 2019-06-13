<?php

namespace Ladb\CoreBundle\Entity\Find\Content;

use Doctrine\ORM\Mapping as ORM;
use Ladb\CoreBundle\Model\LocalisableTrait;
use Ladb\CoreBundle\Model\MultiPicturedTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\LocalisableInterface;

/**
 * @ORM\Table("tbl_find_content_event")
 * @ORM\Entity
 * @LadbAssert\FindEvent(groups={"event"})
 */
class Event extends AbstractContent implements MultiPicturedInterface, LocalisableInterface {

	use MultiPicturedTrait, LocalisableTrait;

	const STATUS_UNKONW = 0;
	const STATUS_SCHEDULED = 1;
	const STATUS_RUNNING = 2;
	const STATUS_COMPLETED = 3;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_find_content_event_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5, groups={"event"})
	 */
	private $pictures;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $location;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $latitude;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $longitude;

	/**
	 * @ORM\Column(name="start_at", type="datetime")
	 */
	private $startAt;

	/**
	 * @ORM\Column(name="start_date", type="date")
	 * @Assert\Date(groups={"event"})
	 * @Assert\NotBlank(groups={"event"})
	 */
	private $startDate;

	/**
	 * @ORM\Column(name="start_time", type="time", nullable=true)
	 * @Assert\Time(groups={"event"})
	 */
	private $startTime;

	/**
	 * @ORM\Column(name="end_at", type="datetime", nullable=false)
	 */
	private $endAt;

	/**
	 * @ORM\Column(name="end_date", type="date", nullable=true)
	 * @Assert\Date(groups={"event"})
	 */
	private $endDate;

	/**
	 * @ORM\Column(name="end_time", type="time", nullable=true)
	 * @Assert\Time(groups={"event"})
	 */
	private $endTime;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 * @Assert\Url(groups={"event"})
	 */
	private $url;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $cancelled = false;

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// StartAt /////

	public function setStartAt($startAt) {
		$this->startAt = $startAt;
		return $this;
	}

	public function getStartAt() {
		return $this->startAt;
	}

	// StartDate /////

	public function setStartDate($startDate) {
		$this->startDate = $startDate;
		return $this;
	}

	public function getStartDate() {
		return $this->startDate;
	}

	// StartTime /////

	public function setStartTime($startTime) {
		$this->startTime = $startTime;
		return $this;
	}

	public function getStartTime() {
		return $this->startTime;
	}

	// EndAt /////

	public function setEndAt($endAt) {
		$this->endAt = $endAt;
		return $this;
	}

	public function getEndAt() {
		return $this->endAt;
	}

	// EndDate /////

	public function setEndDate($endDate) {
		$this->endDate = $endDate;
		return $this;
	}

	public function getEndDate() {
		return $this->endDate;
	}

	// EndTime /////

	public function setEndTime($endTime) {
		$this->endTime = $endTime;
		return $this;
	}

	public function getEndTime() {
		return $this->endTime;
	}

	// Duration /////

	public function getDuration() {
		if (is_null($this->getStartAt()) || is_null($this->getEndAt())) {
			return null;
		}
		return $this->getStartAt()->diff($this->getEndAt());
	}

	// Status /////

	public function getStatus() {
		$now = new \DateTime();
		if ($this->getStartAt() > $now) {
			return self::STATUS_SCHEDULED;
		} else if ($this->getStartAt() <= $now && $this->getEndAt() > $now) {
			return self::STATUS_RUNNING;
		} else if ($this->getEndAt() <= $now) {
			return self::STATUS_COMPLETED;
		}
		return self::STATUS_UNKONW;
	}

	// Url /////

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	public function getUrl() {
		return $this->url;
	}

	// Cancelled /////

	public function setCancelled($cancelled) {
		$this->cancelled = $cancelled;
		return $this;
	}

	public function getCancelled() {
		return $this->cancelled;
	}

}