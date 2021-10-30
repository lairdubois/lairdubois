<?php

namespace App\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;

/**
 * @ORM\Table("tbl_workflow_label")
 * @ORM\Entity(repositoryClass="App\Repository\Workflow\LabelRepository")
 */
class Label {

	const COLOR_SEQUENCE = [ '#61BD4F', '#F2D600', '#FFAB4A', '#EB5A46', '#C377E0', '#0079BF', '#00C2E0', '#51E898', '#FF80CE', '#4D4D4D' ];

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=40)
	 * @Assert\NotBlank()
	 */
	private $name;

	/**
	 * @ORM\Column(type="string", length=7)
	 * @LadbAssert\ValidHexColor()
	 */
	private $color;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Workflow\Workflow", inversedBy="labels")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $workflow;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Name /////

	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	// Color /////

	public function setColor($color) {
		$this->color = $color;
		return $this;
	}

	public function getColor() {
		return $this->color;
	}

	// Workflow /////

	public function setWorkflow(\App\Entity\Workflow\Workflow $workflow = null) {
		$this->workflow = $workflow;
		return $this;
	}

	public function getWorkflow() {
		return $this->workflow;
	}

}