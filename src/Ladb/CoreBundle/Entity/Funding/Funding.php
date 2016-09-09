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
	 * @ORM\Column(name="charge_balance", type="integer")
	 */
	private $chargeBalance = 0;

	/**
	 * @ORM\Column(name="carried_forward_balance", type="integer")
	 */
	private $carriedForwoardBalance = 0;

	/**
	 * @ORM\Column(name="donation_balance", type="integer")
	 */
	private $donationBalance = 0;

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

	// IsCurrent /////

	public function getDate() {
		return new \DateTime($this->getYear().'-'.$this->getMonth().'-01');
	}

	// IsCurrent /////

	public function getIsCurrent() {
		$now = new \DateTime();
		return $this->getMonth() == $now->format('m') && $this->getYear() == $now->format('Y');
	}

	// ChargeBalance /////

	public function incrementChargeBalance($by) {
		$this->chargeBalance += $by;
		return $this;
	}

	public function setChargeBalance($chargeBalance) {
		$this->chargeBalance = $chargeBalance;
		return $this;
	}

	public function getChargeBalance() {
		return $this->chargeBalance;
	}

	public function getChargeBalanceEur() {
		return $this->getChargeBalance() / 100;
	}

	// CarriedForwardBalance /////

	public function setCarriedForwardBalance($carriedForwoardBalance) {
		$this->carriedForwoardBalance = $carriedForwoardBalance;
		return $this;
	}

	public function getCarriedForwardBalance() {
		return $this->carriedForwoardBalance;
	}

	public function getCarriedForwardBalanceEur() {
		return $this->getCarriedForwardBalance() / 100;
	}

	// DonationBalance /////

	public function incrementDonationBalance($by) {
		$this->donationBalance += $by;
		return $this;
	}

	public function setDonationBalance($donationBalance) {
		$this->donationBalance = $donationBalance;
		return $this;
	}

	public function getDonationBalance() {
		return $this->donationBalance;
	}

	public function getDonationBalanceEur() {
		return $this->getDonationBalance() / 100;
	}

	// Balance /////

	public function getBalance() {
		return $this->getCarriedForwardBalance() + $this->getDonationBalance();
	}

	public function getBalanceEur() {
		return $this->getBalance() / 100;
	}

	// Visibilité /////

	public function getVisibility() {
		return max(0, floor($this->getBalance() / $this->getChargeBalance()));
	}

	// PartialVisibilité /////

	public function getPartialVisibility() {
		return max(0, ceil($this->getBalance() / $this->getChargeBalance()));
	}

	// Charges /////

	public function addCharge(\Ladb\CoreBundle\Entity\Funding\Charge $charge) {
		if (!$this->charges->contains($charge)) {
			$this->charges[] = $charge;
			$charge->setFunding($this);
		}
		return $this;
	}

	public function removeCharge(\Ladb\CoreBundle\Entity\Funding\Charge $charge) {
		if ($this->charges->removeElement($charge)) {
			$charge->setFunding(null);
		}
	}

	public function getCharges() {
		return $this->charges;
	}
}
