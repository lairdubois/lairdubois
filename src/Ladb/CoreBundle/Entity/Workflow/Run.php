<?php

namespace Ladb\CoreBundle\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_workflow_run")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Workflow\RunRepository")
 */
class Run {

	const CLASS_NAME = 'LadbCoreBundle:Workflow\Run';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="started_at", type="datetime", nullable=true)
	 */
	private $startedAt;

	/**
	 * @ORM\Column(name="finished_at", type="datetime", nullable=true)
	 */
	private $finishedAt;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Workflow\Task", inversedBy="runs")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $task;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// StartedAt /////

	public function getStartedAt() {
		return $this->startedAt;
	}

	public function setStartedAt($startedAt) {
		$this->startedAt = $startedAt;
		return $this;
	}

	// FinishedAt /////

	public function getFinishedAt() {
		return $this->finishedAt;
	}

	public function setFinishedAt($finishedAt) {
		$this->finishedAt = $finishedAt;
		return $this;
	}

	// Task /////

	public function getTask() {
		return $this->task;
	}

	public function setTask(\Ladb\CoreBundle\Entity\Workflow\Task $task = null) {
		$this->task = $task;
		return $this;
	}

}