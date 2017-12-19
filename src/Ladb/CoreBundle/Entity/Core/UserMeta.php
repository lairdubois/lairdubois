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
	 * @ORM\Column(name="unlisted_wonder_creation_count", type="integer")
	 */
	private $unlistedWonderCreationCount = 0;

	/**
	 * @ORM\Column(name="unlisted_wonder_plan_count", type="integer")
	 */
	private $unlistedWonderPlanCount = 0;

	/**
	 * @ORM\Column(name="unlisted_wonder_workshop_count", type="integer")
	 */
	private $unlistedWonderWorkshopCount = 0;

	/**
	 * @ORM\Column(name="unlisted_find_find_count", type="integer")
	 */
	private $unlistedFindFindCount = 0;

	/**
	 * @ORM\Column(name="unlisted_howto_howto_count", type="integer")
	 */
	private $unlistedHowtoHowtoCount = 0;

	/**
	 * @ORM\Column(name="unlisted_knowledge_wood_count", type="integer")
	 */
	private $unlistedKnowledgeWoodCount = 0;

	/**
	 * @ORM\Column(name="unlisted_knowledge_provider_count", type="integer")
	 */
	private $unlistedKnowledgeProviderCount = 0;

	/**
	 * @ORM\Column(name="unlisted_knowledge_school_count", type="integer")
	 */
	private $unlistedKnowledgeSchoolCount = 0;

	/**
	 * @ORM\Column(name="unlisted_blog_post_count", type="integer")
	 */
	private $unlistedBlogPostCount = 0;

	/**
	 * @ORM\Column(name="unlisted_faq_question_count", type="integer")
	 */
	private $unlistedFaqQuestionCount = 0;

	/**
	 * @ORM\Column(name="unlisted_qa_question_count", type="integer")
	 */
	private $unlistedQaQuestionCount = 0;

	/**
	 * @ORM\Column(name="unlisted_promotion_graphic_count", type="integer")
	 */
	private $unlistedPromotionGraphicCount = 0;

	/**
	 * @ORM\Column(name="unlisted_workflow_workflow_count", type="integer")
	 */
	private $unlistedWorkflowWorkflowCount = 0;


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


	/**
	 * @ORM\Column(name="education_count", type="integer")
	 */
	private $educationCount = 0;


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
			$this->getUnlistedWonderCreationCount() +
			$this->getUnlistedWonderPlanCount() +
			$this->getUnlistedWonderWorkshopCount() +
			$this->getUnlistedFindFindCount() +
			$this->getUnlistedHowtoHowtoCount() +
			$this->getUnlistedKnowledgeWoodCount() +
			$this->getUnlistedKnowledgeProviderCount() +
			$this->getUnlistedKnowledgeSchoolCount() +
			$this->getUnlistedBlogPostCount() +
			$this->getUnlistedFaqQuestionCount() +
			$this->getUnlistedQaQuestionCount() +
			$this->getUnlistedPromotionGraphicCount() +
			$this->getUnlistedWorkflowWorkflowCount()
		;
	}

	// UnlistedDirectoryCount /////

	public function getUnlistedDirectoryCount() {
		return
			$this->getUnlistedKnowledgeProviderCount() +
			$this->getUnlistedKnowledgeSchoolCount()
		;
	}

	// UnlistedWonderCreationCount /////

	public function getUnlistedWonderCreationCount() {
		return $this->unlistedWonderCreationCount;
	}

	public function setUnlistedWonderCreationCount($unlistedWonderCreationCount) {
		$this->unlistedWonderCreationCount = $unlistedWonderCreationCount;
		return $this;
	}

	// UnlistedWonderPlanCount /////

	public function getUnlistedWonderPlanCount() {
		return $this->unlistedWonderPlanCount;
	}

	public function setUnlistedWonderPlanCount($unlistedWonderPlanCount) {
		$this->unlistedWonderPlanCount = $unlistedWonderPlanCount;
		return $this;
	}

	// UnlistedWonderWorkshopCount /////

	public function getUnlistedWonderWorkshopCount() {
		return $this->unlistedWonderWorkshopCount;
	}

	public function setUnlistedWonderWorkshopCount($unlistedWonderWorkshopCount) {
		$this->unlistedWonderWorkshopCount = $unlistedWonderWorkshopCount;
		return $this;
	}

	// UnlistedFindFindCount /////

	public function getUnlistedFindFindCount() {
		return $this->unlistedFindFindCount;
	}

	public function setUnlistedFindFindCount($unlistedFindFindCount) {
		$this->unlistedFindFindCount = $unlistedFindFindCount;
		return $this;
	}

	// UnlistedHowtoHowtoCount /////

	public function getUnlistedHowtoHowtoCount() {
		return $this->unlistedHowtoHowtoCount;
	}

	public function setUnlistedHowtoHowtoCount($unlistedHowtoHowtoCount) {
		$this->unlistedHowtoHowtoCount = $unlistedHowtoHowtoCount;
		return $this;
	}

	// UnlistedKnowledgeWoodCount /////

	public function getUnlistedKnowledgeWoodCount() {
		return $this->unlistedKnowledgeWoodCount;
	}

	public function setUnlistedKnowledgeWoodCount($unlistedKnowledgeWoodCount) {
		$this->unlistedKnowledgeWoodCount = $unlistedKnowledgeWoodCount;
		return $this;
	}

	// UnlistedKnowledgeProviderCount /////

	public function getUnlistedKnowledgeProviderCount() {
		return $this->unlistedKnowledgeProviderCount;
	}

	public function setUnlistedKnowledgeProviderCount($unlistedKnowledgeProviderCount) {
		$this->unlistedKnowledgeProviderCount = $unlistedKnowledgeProviderCount;
		return $this;
	}

	// UnlistedKnowledgeSchoolCount /////

	public function getUnlistedKnowledgeSchoolCount() {
		return $this->unlistedKnowledgeSchoolCount;
	}

	public function setUnlistedKnowledgeSchoolCount($unlistedKnowledgeSchoolCount) {
		$this->unlistedKnowledgeSchoolCount = $unlistedKnowledgeSchoolCount;
		return $this;
	}

	// UnlistedBlogPostCount /////

	public function getUnlistedBlogPostCount() {
		return $this->unlistedBlogPostCount;
	}

	public function setUnlistedBlogPostCount($unlistedBlogPostCount) {
		$this->unlistedBlogPostCount = $unlistedBlogPostCount;
		return $this;
	}

	// UnlistedFaqQuestionCount /////

	public function getUnlistedFaqQuestionCount() {
		return $this->unlistedFaqQuestionCount;
	}

	public function setUnlistedFaqQuestionCount($unlistedFaqQuestionCount) {
		$this->unlistedFaqQuestionCount = $unlistedFaqQuestionCount;
		return $this;
	}

	// UnlistedQaQuestionCount /////

	public function getUnlistedQaQuestionCount() {
		return $this->unlistedQaQuestionCount;
	}

	public function setUnlistedQaQuestionCount($unlistedQaQuestionCount) {
		$this->unlistedQaQuestionCount = $unlistedQaQuestionCount;
		return $this;
	}

	// UnlistedPromotionGraphicCount /////

	public function getUnlistedPromotionGraphicCount() {
		return $this->unlistedPromotionGraphicCount;
	}

	public function setUnlistedPromotionGraphicCount($unlistedPromotionGraphicCount) {
		$this->unlistedPromotionGraphicCount = $unlistedPromotionGraphicCount;
		return $this;
	}

	// UnlistedWorkflowWorkflowCount /////

	public function getUnlistedWorkflowWorkflowCount() {
		return $this->unlistedWorkflowWorkflowCount;
	}

	public function setUnlistedWorkflowWorkflowCount($unlistedWorkflowWorkflowCount) {
		$this->unlistedWorkflowWorkflowCount = $unlistedWorkflowWorkflowCount;
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


	// EducationCount /////

	public function incrementEducationCount($by = 1) {
		return $this->educationCount += intval($by);
	}

	public function getEducationCount() {
		return $this->educationCount;
	}

}