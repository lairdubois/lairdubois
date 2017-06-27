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
	 * @ORM\Column(type="string", length=100, nullable=false)
	 * @Assert\NotBlank(groups={"event"})
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

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// StartAt /////

	public function getStartDate() {
		return $this->startDate;
	}

	public function setStartDate($startDate) {
		$this->startDate = $startDate;
		return $this;
	}

	// StartDate /////

	public function getStartTime() {
		return $this->startTime;
	}

	public function setStartTime($startTime) {
		$this->startTime = $startTime;
		return $this;
	}

	// StartTime /////

	public function getEndDate() {
		return $this->endDate;
	}

	public function setEndDate($endDate) {
		$this->endDate = $endDate;
		return $this;
	}

	// EndAt /////

	public function getEndTime() {
		return $this->endTime;
	}

	public function setEndTime($endTime) {
		$this->endTime = $endTime;
		return $this;
	}

	// EndDate /////

	public function getDuration() {
		if (!is_null($this->getStartAt()) && !is_null($this->getEndAt())) {
			return $this->getStartAt()->diff($this->getEndAt());
		}
		return null;
	}

	public function getStartAt() {
		return $this->startAt;
	}

	// EndTime /////

	public function setStartAt($startAt) {
		$this->startAt = $startAt;
		return $this;
	}

	public function getEndAt() {
		return $this->endAt;
	}

	// Duration /////

	public function setEndAt($endAt) {
		$this->endAt = $endAt;
		return $this;
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

	public function getUrl() {
		return $this->url;
	}

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

}