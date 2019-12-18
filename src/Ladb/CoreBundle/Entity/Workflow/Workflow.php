<?php

namespace Ladb\CoreBundle\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\LinkedToInterface;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Model\CollectionnableTrait;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\InspirableInterface;
use Ladb\CoreBundle\Model\InspirableTrait;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\PicturedTrait;
use Ladb\CoreBundle\Model\SitemapableInterface;
use Ladb\CoreBundle\Model\SitemapableTrait;
use Ladb\CoreBundle\Model\SluggedInterface;
use Ladb\CoreBundle\Model\SluggedTrait;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\TitledTrait;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\ViewableTrait;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\WatchableTrait;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\BodiedTrait;
use Ladb\CoreBundle\Model\CommentableTrait;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\IndexableTrait;
use Ladb\CoreBundle\Model\LikableTrait;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;
use Ladb\CoreBundle\Model\TaggableInterface;
use Ladb\CoreBundle\Model\TaggableTrait;
use Ladb\CoreBundle\Model\LicensedInterface;
use Ladb\CoreBundle\Model\LicensedTrait;

/**
 * @ORM\Table("tbl_workflow")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Workflow\WorkflowRepository")
 */
class Workflow extends AbstractAuthoredPublication implements IndexableInterface, SitemapableInterface, TitledInterface, SluggedInterface, PicturedInterface, BodiedInterface, TaggableInterface, ViewableInterface, LikableInterface, CommentableInterface, WatchableInterface, LicensedInterface, InspirableInterface, CollectionnableInterface, LinkedToInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, BodiedTrait, LicensedTrait;
	use IndexableTrait, SitemapableTrait, LikableTrait, WatchableTrait, CommentableTrait, TaggableTrait, ViewableTrait, InspirableTrait, CollectionnableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Workflow\Workflow';
	const TYPE = 118;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=4)
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
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Task", mappedBy="workflow", cascade={"all"})
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
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Part", mappedBy="workflow", cascade={"all"})
	 */
	protected $parts;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Label", mappedBy="workflow", cascade={"all"})
	 */
	protected $labels;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_workflow_tag")
	 * @Assert\Count(min=2, groups={"public"})
	 */
	private $tags;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\Core\License", cascade={"all"})
	 * @ORM\JoinColumn(nullable=true, name="license_id")
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\License")
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
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Workflow", mappedBy="inspirations")
	 */
	private $rebounds;

	/**
	 * @ORM\Column(type="integer", name="inspiration_count")
	 */
	private $inspirationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Workflow", inversedBy="rebounds", cascade={"persist"})
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
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Creation", mappedBy="workflows")
	 */
	private $creations;

	/**
	 * @ORM\Column(type="integer", name="plan_count")
	 */
	private $planCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Plan", inversedBy="workflows", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_workflow_plan")
	 * @Assert\Count(min=0, max=4)
	 */
	private $plans;

	/**
	 * @ORM\Column(type="integer", name="workshop_count")
	 */
	private $workshopCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Workshop", mappedBy="workflows")
	 */
	private $workshops;

	/**
	 * @ORM\Column(type="integer", name="howto_count")
	 */
	private $howtoCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Howto\Howto", mappedBy="workflows")
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

	public function addTask(\Ladb\CoreBundle\Entity\Workflow\Task $task) {
		if (!$this->tasks->contains($task)) {
			$this->tasks[] = $task;
			$task->setWorkflow($this);
		}
		return $this;
	}

	public function removeTask(\Ladb\CoreBundle\Entity\Workflow\Task $task) {
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

	public function addPart(\Ladb\CoreBundle\Entity\Workflow\Part $part) {
		if (!$this->parts->contains($part)) {
			$this->parts[] = $part;
			$part->setWorkflow($this);
		}
		return $this;
	}

	public function removePart(\Ladb\CoreBundle\Entity\Workflow\Part $part) {
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

	public function addLabel(\Ladb\CoreBundle\Entity\Workflow\Label $label) {
		if (!$this->labels->contains($label)) {
			$this->labels[] = $label;
			$label->setWorkflow($this);
		}
		return $this;
	}

	public function removeLabel(\Ladb\CoreBundle\Entity\Workflow\Label $label) {
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

	public function addPlan(\Ladb\CoreBundle\Entity\Wonder\Plan $plan) {
		if (!$this->plans->contains($plan)) {
			$this->plans[] = $plan;
			$this->planCount = count($this->plans);
			if ($this->getIsPublic()) {
				$plan->incrementWorkflowCount();
			}
		}
		return $this;
	}

	public function removePlan(\Ladb\CoreBundle\Entity\Wonder\Plan $plan) {
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