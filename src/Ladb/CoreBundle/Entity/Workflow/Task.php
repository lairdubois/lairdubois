<?php

namespace Ladb\CoreBundle\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_workflow_task")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Workflow\TaskRepository")
 */
class Task {

	const CLASS_NAME = 'LadbCoreBundle:Workflow\Task';

	const STATUS_UNKNOW = 0;
	const STATUS_PENDING = 1;
	const STATUS_WORKABLE = 2;
	const STATUS_RUNNING = 3;
	const STATUS_DONE = 4;

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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Workflow\Workflow", inversedBy="tasks")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $workflow;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 */
	private $title;

	/**
	 * @ORM\Column(type="integer", name="position_left")
	 */
	private $positionLeft = 0;

	/**
	 * @ORM\Column(type="integer", name="position_top")
	 */
	private $positionTop = 0;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $status = self::STATUS_UNKNOW;

	/**
	 * @ORM\Column(name="started_at", type="datetime", nullable=true)
	 */
	private $startedAt;

	/**
	 * @ORM\Column(name="last_running_at", type="datetime", nullable=true)
	 */
	private $lastRunningAt;

	/**
	 * @ORM\Column(name="finished_at", type="datetime", nullable=true)
	 */
	private $finishedAt;

	/**
	 * @ORM\Column(type="integer", name="estimated_duration")
	 * @Assert\GreaterThanOrEqual(0)
	 */
	private $estimatedDuration = 0;

	/**
	 * @ORM\Column(type="integer")
	 * @Assert\GreaterThanOrEqual(0)
	 */
	private $duration = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Label", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_workflow_task_label")
	 * @ORM\OrderBy({"id" = "ASC"})
	 */
	protected $labels;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Part", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_workflow_task_part")
	 * @ORM\OrderBy({"name" = "ASC"})
	 */
	protected $parts;

	/**
	 * @ORM\Column(type="integer", name="part_count")
	 */
	private $partCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Task", mappedBy="targetTasks")
	 */
	private $sourceTasks;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Task", inversedBy="sourceTasks")
	 * @ORM\JoinTable(
	 *      name="tbl_workflow_task_connection",
	 *      joinColumns={@ORM\JoinColumn(name="from_task_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="to_task_id", referencedColumnName="id")}
	 * )
	 */
	private $targetTasks;

	/////

	public function __construct() {
		$this->labels = new \Doctrine\Common\Collections\ArrayCollection();
		$this->parts = new \Doctrine\Common\Collections\ArrayCollection();
		$this->sourceTasks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->targetTasks = new \Doctrine\Common\Collections\ArrayCollection();
	}

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

	// Workflow /////

	public function setWorkflow(\Ladb\CoreBundle\Entity\Workflow\Workflow $workflow = null) {
		$this->workflow = $workflow;
		return $this;
	}

	public function getWorkflow() {
		return $this->workflow;
	}

	// Title /////

	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	public function getTitle() {
		return $this->title;
	}

	// PositionLeft /////

	public function setPositionLeft($positionLeft) {
		$this->positionLeft = $positionLeft;
		return $this;
	}

	public function getPositionLeft() {
		return $this->positionLeft;
	}

	// PositionTop /////

	public function setPositionTop($positionTop) {
		$this->positionTop = $positionTop;
		return $this;
	}

	public function getPositionTop() {
		return $this->positionTop;
	}

	// Status /////

	public function setStatus($status) {
		$this->status = $status;
		return $this;
	}

	public function getStatus() {
		return $this->status;
	}

	// StartedAt /////

	public function setStartedAt($startedAt) {
		$this->startedAt = $startedAt;
		return $this;
	}

	public function getStartedAt() {
		return $this->startedAt;
	}

	// LastRunningAt /////

	public function setLastRunningAt($lastRunningAt) {
		$this->lastRunningAt = $lastRunningAt;
		return $this;
	}

	public function getLastRunningAt() {
		return $this->lastRunningAt;
	}

	// FinishedAt /////

	public function setFinishedAt($finishedAt) {
		$this->finishedAt = $finishedAt;
		return $this;
	}

	public function getFinishedAt() {
		return $this->finishedAt;
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

	// Labels /////

	public function addLabel(\Ladb\CoreBundle\Entity\Workflow\Label $label) {
		if (!$this->labels->contains($label) && (is_null($label->getWorkflow()) || $label->getWorkflow() === $this->getWorkflow())) {
			$this->labels[] = $label;
			if (!is_null($this->getWorkflow()) && is_null($label->getWorkflow())) {
				$this->getWorkflow()->addLabel($label);
			}
		}
		return $this;
	}

	public function removeLabel(\Ladb\CoreBundle\Entity\Workflow\Label $label) {
		$this->labels->removeElement($label);
	}

	public function getLabels() {
		return $this->labels;
	}

	// Parts /////

	public function addPart(\Ladb\CoreBundle\Entity\Workflow\Part $part) {
		if (!$this->parts->contains($part) && (is_null($part->getWorkflow()) || $part->getWorkflow() === $this->getWorkflow())) {
			$this->parts[] = $part;
			if (!is_null($this->getWorkflow()) && is_null($part->getWorkflow())) {
				$this->getWorkflow()->addPart($part);
			}
		}
		return $this;
	}

	public function removePart(\Ladb\CoreBundle\Entity\Workflow\Part $part) {
		$this->parts->removeElement($part);
	}

	public function getParts() {
		return $this->parts;
	}

	// PartCount /////

	public function setPartCount($partCount) {
		$this->partCount = $partCount;
		return $this;
	}

	public function getPartCount() {
		return $this->partCount;
	}

	// SourceTasks /////

	public function getSourceTasks() {
		return $this->sourceTasks;
	}

	// TargetTasks /////

	public function addTargetTask(\Ladb\CoreBundle\Entity\Workflow\Task $targetTask = null) {
		if (!$this->targetTasks->contains($targetTask)) {
			$this->targetTasks[] = $targetTask;
		}
		return $this;
	}

	public function removeTargetTask(\Ladb\CoreBundle\Entity\Workflow\Task $targetTask) {
		$this->targetTasks->removeElement($targetTask);
	}

	public function getTargetTasks() {
		return $this->targetTasks;
	}

}