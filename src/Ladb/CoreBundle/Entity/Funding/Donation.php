<?php

namespace Ladb\CoreBundle\Entity\Funding;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_funding_donation")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Funding\DonationRepository")
 */
class Donation {

	const CLASS_NAME = 'LadbCoreBundle:Funding\Donation';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	private $createdAt;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $amount = 0;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $fee = 0;

	/**
	 * @ORM\Column(name="stripe_charge_id", type="string", length=30, unique=true)
	 */
	private $stripeChargeId;

	/////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// User /////

	public function setUser(\Ladb\CoreBundle\Entity\User $user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	// Amount /////

	public function setAmount($amount) {
		$this->amount = $amount;
		return $this;
	}

	public function getAmount() {
		return $this->amount;
	}

	// Fee /////

	public function setFee($fee) {
		$this->fee = $fee;
		return $this;
	}

	public function getFee() {
		return $this->fee;
	}

	// StripeChargeId /////

	public function setStripeChargeId($stripeChargeId) {
		$this->stripeChargeId = $stripeChargeId;
		return $this;
	}

	public function getStripeChargeId() {
		return $this->stripeChargeId;
	}

}
