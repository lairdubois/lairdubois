<?php

namespace Ladb\CoreBundle\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\TaggableInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;

/**
 * @ORM\Table("tbl_workflow")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Workflow\WorkflowRepository")
 */
class Workflow extends AbstractAuthoredPublication implements TaggableInterface {

	const CLASS_NAME = 'LadbCoreBundle:Workflow\Workflow';
	const TYPE = 113;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=4)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’ʼ#,.:%?!-]+$/", message="default.title.regex")
	 * @LadbAssert\UpperCaseRatio()
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

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Workflow\Label", mappedBy="workflow", cascade={"all"})
	 */
	protected $labels;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_workflow_tag")
	 */
	private $tags;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\License", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, name="license_id")
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\License")
	 */
	private $license;

	/////

	public function __construct() {
		$this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->labels = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
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

	// Tags /////

	public function addTag(\Ladb\CoreBundle\Entity\Tag $tag) {
		$this->tags[] = $tag;
		return $this;
	}

	public function removeTag(\Ladb\CoreBundle\Entity\Tag $tag) {
		$this->tags->removeElement($tag);
	}

	public function getTags() {
		return $this->tags;
	}

	// License /////

	public function setLicense($license) {
		$this->license = $license;
	}

	public function getLicense() {
		if (is_null($this->license)) {
			return new \Ladb\CoreBundle\Entity\License();
		}
		return $this->license;
	}

}