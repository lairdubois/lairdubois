<?php

namespace App\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;

/**
 * @ORM\Table("tbl_workflow_part")
 * @ORM\Entity(repositoryClass="App\Repository\Workflow\PartRepository")
 */
class Part {

	const CLASS_NAME = 'App\Entity\Workflow\Part';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=10, nullable=true)
	 */
	private $number;

	/**
	 * @ORM\Column(type="string", length=40)
	 * @Assert\NotBlank()
	 */
	private $name;

	/**
	 * @ORM\Column(type="integer")
	 * @Assert\GreaterThan(0)
	 */
	private $count = 1;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Workflow\Workflow", inversedBy="parts")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $workflow;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Number /////

	public function setNumber($number) {
		$this->number = $number;
		return $this;
	}

	public function getNumber() {
		return $this->number;
	}

	// Name /////

	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	// Count /////

	public function setCount($count) {
		$this->count = $count;
		return $this;
	}

	public function getCount() {
		return $this->count;
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