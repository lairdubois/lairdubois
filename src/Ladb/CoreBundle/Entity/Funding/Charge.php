<?php

namespace Ladb\CoreBundle\Entity\Funding;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_funding_charge")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Funding\DonationRepository")
 */
class Charge {

	const CLASS_NAME = 'LadbCoreBundle:Funding\Charge';

	const TYPE_UNKNOW = 0;
	const TYPE_HOSTING = 1;

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="duty_free_amount", type="integer")
	 */
	private $dutyFreeAmount = 0;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $amount = 0;

	/**
	 * @ORM\Column(type="string")
	 * @Assert\NotBlank()
	 */
	private $label = '';

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $isRecurrent = false;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Funding\Funding", inversedBy="charges")
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $funding = null;

	/////

	public function getId() {
		return $this->id;
	}

	// DutyFreeAmount /////

	public function setDutyFreeAmount($dutyFreeAmount) {
		$this->dutyFreeAmount = $dutyFreeAmount;
		return $this;
	}

	public function getDutyFreeAmount() {
		return $this->dutyFreeAmount;
	}

	public function getDutyFreeAmountEur() {
		return $this->getDutyFreeAmount() / 100;
	}

	// Amount /////

	public function setAmount($amount) {
		$this->amount = $amount;
		return $this;
	}

	public function getAmount() {
		return $this->amount;
	}

	public function getAmountEur() {
		return $this->getAmount() / 100;
	}

	// Label /////

	public function setLabel($label) {
		$this->label = $label;
		return $this;
	}

	public function getLabel() {
		return $this->label;
	}

	// IsRecurrent /////

	public function setIsRecurrent($isRecurrent) {
		$this->isRecurrent = $isRecurrent;
		return $this;
	}

	public function getIsRecurrent() {
		return $this->isRecurrent;
	}

	// Funding /////

	public function setFunding($funding) {
		$this->funding = $funding;
		return $this;
	}

	public function getFunding() {
		return $this->funding;
	}

}
