<?php

namespace Ladb\CoreBundle\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;

/**
 * @ORM\Table("tbl_workflow")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Workflow\WorkflowRepository")
 */
class Workflow extends AbstractAuthoredPublication {

	const CLASS_NAME = 'LadbCoreBundle:Workflow\Workflow';
	const TYPE = 113;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=4)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’ʼ#,.:%?!-]+$/", message="default.title.regex")
	 * @ladbAssert\UpperCaseRatio()
	 */
	private $title;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $duration = 0;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Task", mappedBy="workflow", cascade={"all"})
	 */
	protected $tasks;

	/////

	public function __construct() {
		$this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Type /////

	public function getType() {
		return Workflow::TYPE;
	}

	// Title /////

	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	public function getTitle() {
		return $this->title;
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

}