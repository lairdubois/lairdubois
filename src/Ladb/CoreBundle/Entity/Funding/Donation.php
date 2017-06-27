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
	 * @ORM\Column(type="string", length=20, nullable=true)
	 */
	private $hashid;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	private $createdAt;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
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

	// Hashid /////

	public function getHashid() {
		return $this->hashid;
	}

	public function setHashid($hashid) {
		$this->hashid = $hashid;
		return $this;
	}

	// CreatedAt /////

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// Age /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// User /////

	public function getUser() {
		return $this->user;
	}

	public function setUser(\Ladb\CoreBundle\Entity\Core\User $user) {
		$this->user = $user;
		return $this;
	}

	// Amount /////

	public function getAmountEur() {
		return $this->getAmount() / 100;
	}

	public function getAmount() {
		return $this->amount;
	}

	public function setAmount($amount) {
		$this->amount = $amount;
		return $this;
	}

	// Fee /////

	public function getFeeEur() {
		return $this->getFee() / 100;
	}

	public function getFee() {
		return $this->fee;
	}

	public function setFee($fee) {
		$this->fee = $fee;
		return $this;
	}

	// StripeChargeId /////

	public function getStripeChargeId() {
		return $this->stripeChargeId;
	}

	public function setStripeChargeId($stripeChargeId) {
		$this->stripeChargeId = $stripeChargeId;
		return $this;
	}

}
