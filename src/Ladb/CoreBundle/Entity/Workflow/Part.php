<?php

namespace Ladb\CoreBundle\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;

/**
 * @ORM\Table("tbl_workflow_part")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Workflow\PartRepository")
 */
class Part {

	const CLASS_NAME = 'LadbCoreBundle:Workflow\Part';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=10)
	 */
	private $number;

	/**
	 * @ORM\Column(type="string", length=40)
	 * @Assert\NotBlank()
	 */
	private $name;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $count;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Workflow\Workflow", inversedBy="labels")
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

	public function setWorkflow(\Ladb\CoreBundle\Entity\Workflow\Workflow $workflow = null) {
		$this->workflow = $workflow;
		return $this;
	}

	public function getWorkflow() {
		return $this->workflow;
	}

}