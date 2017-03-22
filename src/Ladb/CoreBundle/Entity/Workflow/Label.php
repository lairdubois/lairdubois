<?php

namespace Ladb\CoreBundle\Entity\Workflow;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_workflow_label")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Workflow\LabelRepository")
 */
class Label {

	const CLASS_NAME = 'LadbCoreBundle:Workflow\Label';
	const COLOR_SEQUENCE = [ 'ee0701', 'fbca04', '0e8a16', '006b75', '1d76db', '5319e7' ];

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=40)
	 */
	private $name;

	/**
	 * @ORM\Column(type="string", length=6)
	 */
	private $color;

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

	public function setWorkflow(\Ladb\CoreBundle\Entity\Workflow\Workflow $workflow = null) {
		$this->workflow = $workflow;
		return $this;
	}

	public function getWorkflow() {
		return $this->workflow;
	}

}