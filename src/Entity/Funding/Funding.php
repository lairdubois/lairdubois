<?php

namespace App\Entity\Funding;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_funding")
 * @ORM\Entity(repositoryClass="App\Repository\Funding\FundingRepository")
 */
class Funding {

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
	 * @ORM\Column(name="donation_fee_balance", type="integer")
	 */
	private $donationFeeBalance = 0;

	/**
	 * @ORM\Column(name="carried_forward_balance", type="integer")
	 */
	private $carriedForwoardBalance = 0;

	/**
	 * @ORM\Column(name="donation_balance", type="integer")
	 */
	private $donationBalance = 0;

	/**
	 * @ORM\Column(name="donation_count", type="integer")
	 */
	private $donationCount = 0;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Funding\Charge", mappedBy="funding", cascade={"all"})
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

	// DonationFeeBalance /////

	public function incrementDonationFeeBalance($by) {
		$this->donationFeeBalance += $by;
		return $this;
	}

	public function setDonationFeeBalance($donationFeeBalance) {
		$this->donationFeeBalance = $donationFeeBalance;
		return $this;
	}

	public function getDonationFeeBalance() {
		return $this->donationFeeBalance;
	}

	public function getDonationFeeBalanceEur() {
		return $this->getDonationFeeBalance() / 100;
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

	// DonationCount /////

	public function incrementDonationCount($by = 1) {
		$this->donationCount += $by;
		return $this;
	}

	public function getDonationCount() {
		return $this->donationCount;
	}

	// Charges /////

	public function addCharge(\App\Entity\Funding\Charge $charge) {
		if (!$this->charges->contains($charge)) {
			$this->charges[] = $charge;
			$charge->setFunding($this);
		}
		return $this;
	}

	public function removeCharge(\App\Entity\Funding\Charge $charge) {
		if ($this->charges->removeElement($charge)) {
			$charge->setFunding(null);
		}
	}

	public function getCharges() {
		return $this->charges;
	}

	/////

	// OutgoingsBalance /////

	public function getOutgoingsBalance() {
		return $this->getChargeBalance() + $this->getDonationFeeBalance();
	}

	public function getOutgoingsBalanceEur() {
		return $this->getOutgoingsBalance() / 100;
	}

	// EarningsBalance /////

	public function getEarningsBalance() {
		return $this->getCarriedForwardBalance() + $this->getDonationBalance();
	}

	public function getEarningsBalanceEur() {
		return $this->getEarningsBalance() / 100;
	}

	// Visibility /////

	public function getVisibility() {
		return max(0, floor(($this->getEarningsBalance() - $this->getDonationFeeBalance()) / $this->getChargeBalance()));
	}

	// PartialVisibility /////

	public function getPartialVisibility() {
		return max(0, ceil(($this->getEarningsBalance() - $this->getDonationFeeBalance()) / $this->getChargeBalance()));
	}

}
