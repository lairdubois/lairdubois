<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_user_meta")
 * @ORM\Entity
 */
class UserMeta {

	const CLASS_NAME = 'LadbCoreBundle:Core\UserMeta';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User", inversedBy="meta")
	 */
	private $user;


	/**
	 * @ORM\Column(name="unlisted_creation_count", type="integer")
	 */
	private $unlistedCreationCount = 0;

	/**
	 * @ORM\Column(name="unlisted_plan_count", type="integer")
	 */
	private $unlistedPlanCount = 0;

	/**
	 * @ORM\Column(name="unlisted_workshop_count", type="integer")
	 */
	private $unlistedWorkshopCount = 0;

	/**
	 * @ORM\Column(name="unlisted_find_count", type="integer")
	 */
	private $unlistedFindCount = 0;

	/**
	 * @ORM\Column(name="unlisted_howto_count", type="integer")
	 */
	private $unlistedHowtoCount = 0;

	/**
	 * @ORM\Column(name="unlisted_wood_count", type="integer")
	 */
	private $unlistedWoodCount = 0;

	/**
	 * @ORM\Column(name="unlisted_provider_count", type="integer")
	 */
	private $unlistedProviderCount = 0;

	/**
	 * @ORM\Column(name="unlisted_post_count", type="integer")
	 */
	private $unlistedPostCount = 0;

	/**
	 * @ORM\Column(name="unlisted_question_count", type="integer")
	 */
	private $unlistedQuestionCount = 0;


	/**
	 * @ORM\Column(name="donation_count", type="integer")
	 */
	private $donationCount = 0;

	/**
	 * @ORM\Column(name="donation_balance", type="integer")
	 */
	private $donationBalance = 0;

	/**
	 * @ORM\Column(name="donation_fee_balance", type="integer")
	 */
	private $donationFeeBalance = 0;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// User /////

	public function getUser() {
		return $this->user;
	}

	public function setUser($user) {
		$this->user = $user;
		return $this;
	}

	// UnlistedCount /////

	public function getUnlistedCount() {
		return
			$this->getUnlistedCreationCount() +
			$this->getUnlistedPlanCount() +
			$this->getUnlistedWorkshopCount() +
			$this->getUnlistedFindCount() +
			$this->getUnlistedHowtoCount() +
			$this->getUnlistedProviderCount() +
			$this->getUnlistedWoodCount() +
			$this->getUnlistedPostCount() +
			$this->getUnlistedQuestionCount()
		;
	}

	// UnlistedCreationCount /////

	public function getUnlistedCreationCount() {
		return $this->unlistedCreationCount;
	}

	public function setUnlistedCreationCount($unlistedCreationCount) {
		$this->unlistedCreationCount = $unlistedCreationCount;
		return $this;
	}

	// UnlistedPlanCount /////

	public function getUnlistedPlanCount() {
		return $this->unlistedPlanCount;
	}

	public function setUnlistedPlanCount($unlistedPlanCount) {
		$this->unlistedPlanCount = $unlistedPlanCount;
		return $this;
	}

	// UnlistedWorkshopCount /////

	public function getUnlistedWorkshopCount() {
		return $this->unlistedWorkshopCount;
	}

	public function setUnlistedWorkshopCount($unlistedWorkshopCount) {
		$this->unlistedWorkshopCount = $unlistedWorkshopCount;
		return $this;
	}

	// UnlistedFindCount /////

	public function getUnlistedFindCount() {
		return $this->unlistedFindCount;
	}

	public function setUnlistedFindCount($unlistedFindCount) {
		$this->unlistedFindCount = $unlistedFindCount;
		return $this;
	}

	// UnlistedHowtoCount /////

	public function getUnlistedHowtoCount() {
		return $this->unlistedHowtoCount;
	}

	public function setUnlistedHowtoCount($unlistedHowtoCount) {
		$this->unlistedHowtoCount = $unlistedHowtoCount;
		return $this;
	}

	// UnlistedWoodCount /////

	public function getUnlistedProviderCount() {
		return $this->unlistedProviderCount;
	}

	public function setUnlistedProviderCount($unlistedProviderCount) {
		$this->unlistedProviderCount = $unlistedProviderCount;
		return $this;
	}

	// UnlistedProviderCount /////

	public function getUnlistedWoodCount() {
		return $this->unlistedWoodCount;
	}

	public function setUnlistedWoodCount($unlistedWoodCount) {
		$this->unlistedWoodCount = $unlistedWoodCount;
		return $this;
	}

	// UnlistedPostCount /////

	public function getUnlistedPostCount() {
		return $this->unlistedPostCount;
	}

	public function setUnlistedPostCount($unlistedBlogPostCount) {
		$this->unlistedPostCount = $unlistedBlogPostCount;
		return $this;
	}

	// UnlistedQuestionCount /////

	public function getUnlistedQuestionCount() {
		return $this->unlistedQuestionCount;
	}

	public function setUnlistedQuestionCount($unlistedFaqQuestionCount) {
		$this->unlistedQuestionCount = $unlistedFaqQuestionCount;
		return $this;
	}


	// DonationCount /////

	public function incrementDonationCount($by = 1) {
		return $this->donationCount += intval($by);
	}

	public function getDonationCount() {
		return $this->donationCount;
	}

	// DonationBalance /////

	public function incrementDonationBalance($by = 1) {
		return $this->donationBalance += intval($by);
	}

	public function getDonationBalanceEur() {
		return $this->getDonationBalance() / 100;
	}

	public function getDonationBalance() {
		return $this->donationBalance;
	}

	// DonationFeeBalance /////

	public function incrementDonationFeeBalance($by = 1) {
		return $this->donationFeeBalance += intval($by);
	}

	public function getDonationFeeBalanceEur() {
		return $this->getDonationFeeBalance() / 100;
	}

	public function getDonationFeeBalance() {
		return $this->donationFeeBalance;
	}

}