<?php

namespace Ladb\CoreBundle\Entity\Funding;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_funding")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Funding\FundingRepository")
 */
class Funding {

	const CLASS_NAME = 'LadbCoreBundle:Funding\Funding';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $month;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $year;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $chargeAmount;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $donationAmount;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $carriedForwoardDonationAmount;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $currentDonationAmount;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Funding\Charge", mappedBy="funding", cascade={"all"})
	 */
	private $charges;

	/////

	public function __construct() {
		$this->charges = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	public function getId() {
		return $this->id;
	}

	// Month /////

	public function setMonth($month) {
		$this->month = $month;
		return $this;
	}

	public function getMonth() {
		return $this->month;
	}

	// Year /////

	public function setYear($year) {
		$this->year = $year;
		return $this;
	}

	public function getYear() {
		return $this->year;
	}

	// ChargeAmount /////

	public function setChargeAmount($chargeAmount) {
		$this->chargeAmount = $chargeAmount;
		return $this;
	}

	public function getChargeAmount() {
		return $this->chargeAmount;
	}

	// DonationAmount /////

	public function setDonationAmount($donationAmount) {
		$this->donationAmount = $donationAmount;
		return $this;
	}

	public function getDonationAmount() {
		return $this->donationAmount;
	}

	// Charges /////

	public function setCharges($charges) {
		$this->charges = $charges;
		return $this;
	}

	public function getCharges() {
		return $this->charges;
	}
}
