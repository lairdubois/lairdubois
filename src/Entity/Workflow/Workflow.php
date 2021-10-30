<?php

namespace App\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Model\LinkedToInterface;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\CommentableInterface;
use App\Model\InspirableInterface;
use App\Model\InspirableTrait;
use App\Model\LikableInterface;
use App\Model\PicturedInterface;
use App\Model\PicturedTrait;
use App\Model\SitemapableInterface;
use App\Model\SitemapableTrait;
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
use App\Model\LicensedInterface;
use App\Model\LicensedTrait;

/**
 * @ORM\Table("tbl_workflow")
 * @ORM\Entity(repositoryClass="App\Repository\Workflow\WorkflowRepository")
 */
class Workflow extends AbstractAuthoredPublication implements IndexableInterface, SitemapableInterface, TitledInterface, SluggedInterface, PicturedInterface, HtmlBodiedInterface, TaggableInterface, ViewableInterface, LikableInterface, CommentableInterface, WatchableInterface, LicensedInterface, InspirableInterface, CollectionnableInterface, LinkedToInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, HtmlBodiedTrait, LicensedTrait;
	use IndexableTrait, SitemapableTrait, LikableTrait, WatchableTrait, CommentableTrait, TaggableTrait, ViewableTrait, InspirableTrait, CollectionnableTrait;

	const TYPE = 118;

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
	 * @ORM\Column(type="integer", name="estimated_duration")
	 */
	private $estimatedDuration = 0;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $duration = 0;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Workflow\Task", mappedBy="workflow", cascade={"all"})
	 * @ORM\OrderBy({"positionTop" = "ASC", "positionLeft" = "ASC"})
	 */
	protected $tasks;

	/**
	 * @ORM\Column(type="integer", name="task_count")
	 */
	private $taskCount = 0;

	/**
	 * @ORM\Column(type="integer", name="running_task_count")
	 */
	private $runningTaskCount = 0;

	/**
	 * @ORM\Column(type="integer", name="done_task_count")
	 */
	private $doneTaskCount = 0;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Workflow\Part", mappedBy="workflow", cascade={"all"})
	 */
	protected $parts;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Workflow\Label", mappedBy="workflow", cascade={"all"})
	 */
	protected $labels;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_workflow_tag")
	 */
	private $tags;

	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\Core\License", cascade={"all"})
	 * @ORM\JoinColumn(nullable=true, name="license_id")
	 * @Assert\Type(type="App\Entity\Core\License")
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

	/**
	 * @ORM\Column(type="integer", name="copy_count")
	 */
	private $copyCount = 0;

	/**
	 * @ORM\Column(type="integer", name="rebound_count")
	 */
	private $reboundCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Workflow\Workflow", mappedBy="inspirations")
	 */
	private $rebounds;

	/**
	 * @ORM\Column(type="integer", name="inspiration_count")
	 */
	private $inspirationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Workflow\Workflow", inversedBy="rebounds", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_workflow_inspiration",
	 *      	joinColumns={ @ORM\JoinColumn(name="workflow_id", referencedColumnName="id") },
	 *      	inverseJoinColumns={ @ORM\JoinColumn(name="rebound_workflow_id", referencedColumnName="id") }
	 *      )
	 */
	private $inspirations;

	/**
	 * @ORM\Column(type="integer", name="creation_count")
	 */
	private $creationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Creation", mappedBy="workflows")
	 */
	private $creations;

	/**
	 * @ORM\Column(type="integer", name="plan_count")
	 */
	private $planCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Plan", inversedBy="workflows", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_workflow_plan")
	 * @Assert\Count(min=0, max=4)
	 */
	private $plans;

	/**
	 * @ORM\Column(type="integer", name="workshop_count")
	 */
	private $workshopCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Workshop", mappedBy="workflows")
	 */
	private $workshops;

	/**
	 * @ORM\Column(type="integer", name="howto_count")
	 */
	private $howtoCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Howto\Howto", mappedBy="workflows")
	 */
	private $howtos;

	/////

	public function __construct() {
		$this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->parts = new \Doctrine\Common\Collections\ArrayCollection();
		$this->labels = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
		$this->inspirations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->plans = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER;
	}

	// Type /////

	public function getType() {
		return Workflow::TYPE;
	}

	// EstimatedDuration /////

	public function incrementEstimatedDuration($by = 0) {
		return $this->estimatedDuration += intval($by);
	}

	public function setEstimatedDuration($estimatedDuration) {
		$this->estimatedDuration = $estimatedDuration;
		return $this;
	}

	public function getEstimatedDuration() {
		return $this->estimatedDuration;
	}

	// Duration /////

	public function incrementDuration($by = 0) {
		return $this->duration += intval($by);
	}

	public function setDuration($duration) {
		$this->duration = $duration;
		return $this;
	}

	public function getDuration() {
		return $this->duration;
	}

	// Tasks /////

	public function addTask(\App\Entity\Workflow\Task $task) {
		if (!$this->tasks->contains($task)) {
			$this->tasks[] = $task;
			$task->setWorkflow($this);
		}
		return $this;
	}

	public function removeTask(\App\Entity\Workflow\Task $task) {
		if ($this->tasks->removeElement($task)) {
			$task->setWorkflow(null);
		}
	}

	public function getTasks() {
		return $this->tasks;
	}

	public function resetTasks() {
		$this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// TaskCount /////

	public function incrementTaskCount($by = 1) {
		return $this->taskCount += intval($by);
	}

	public function getTaskCount() {
		return $this->taskCount;
	}

	// RunningTaskCount /////

	public function incrementRunningTaskCount($by = 1) {
		return $this->runningTaskCount += intval($by);
	}

	public function getRunningTaskCount() {
		return $this->runningTaskCount;
	}

	// DoneTaskCount /////

	public function incrementDoneTaskCount($by = 1) {
		return $this->doneTaskCount += intval($by);
	}

	public function getDoneTaskCount() {
		return $this->doneTaskCount;
	}

	// Parts /////

	public function addPart(\App\Entity\Workflow\Part $part) {
		if (!$this->parts->contains($part)) {
			$this->parts[] = $part;
			$part->setWorkflow($this);
		}
		return $this;
	}

	public function removePart(\App\Entity\Workflow\Part $part) {
		if ($this->parts->removeElement($part)) {
			$part->setWorkflow(null);
		}
	}

	public function getParts() {
		return $this->parts;
	}

	public function resetParts() {
		$this->parts = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Labels /////

	public function addLabel(\App\Entity\Workflow\Label $label) {
		if (!$this->labels->contains($label)) {
			$this->labels[] = $label;
			$label->setWorkflow($this);
		}
		return $this;
	}

	public function removeLabel(\App\Entity\Workflow\Label $label) {
		if ($this->labels->removeElement($label)) {
			$label->setWorkflow(null);
		}
	}

	public function getLabels() {
		return $this->labels;
	}

	public function resetLabels() {
		$this->labels = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// CopyCount /////

	public function incrementCopyCount($by = 1) {
		return $this->copyCount += intval($by);
	}

	public function getCopyCount() {
		return $this->copyCount;
	}

	// LinkedEntities /////

	public function getLinkedEntities() {
		return array_merge(
			$this->inspirations->getValues()
		);
	}

	// CreationCount /////

	public function incrementCreationCount($by = 1) {
		return $this->creationCount += intval($by);
	}

	public function getCreationCount() {
		return $this->creationCount;
	}

	// Creations /////

	public function getCreations() {
		return $this->creations;
	}

	// PlanCount /////

	public function incrementPlanCount($by = 1) {
		return $this->planCount += intval($by);
	}

	public function getPlanCount() {
		return $this->planCount;
	}

	// Plans /////

	public function addPlan(\App\Entity\Wonder\Plan $plan) {
		if (!$this->plans->contains($plan)) {
			$this->plans[] = $plan;
			$this->planCount = count($this->plans);
			if ($this->getIsPublic()) {
				$plan->incrementWorkflowCount();
			}
		}
		return $this;
	}

	public function removePlan(\App\Entity\Wonder\Plan $plan) {
		if ($this->plans->removeElement($plan)) {
			$this->planCount = count($this->plans);
			if ($this->getIsPublic()) {
				$plan->incrementWorkflowCount(-1);
			}
		}
	}

	public function getPlans() {
		return $this->plans;
	}

	// WorkshopCount /////

	public function incrementWorkshopCount($by = 1) {
		return $this->workshopCount += intval($by);
	}

	public function getWorkshopCount() {
		return $this->workshopCount;
	}

	// Workshops /////

	public function getWorkshops() {
		return $this->workshops;
	}

	// HowtoCount /////

	public function incrementHowtoCount($by = 1) {
		return $this->howtoCount += intval($by);
	}

	public function getHowtoCount() {
		return $this->howtoCount;
	}

	// Howtos /////

	public function getHowtos() {
		return $this->howtos;
	}

}