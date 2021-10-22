<?php

namespace App\Entity\Wonder;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Model\BlockBodiedTrait;
use App\Model\InspirableInterface;
use App\Model\InspirableTrait;
use App\Model\BlockBodiedInterface;
use App\Model\FeedbackableInterface;
use App\Model\FeedbackableTrait;
use App\Model\SpotlightableInterface;
use App\Model\SpotlightableTrait;

/**
 * @ORM\Table("tbl_wonder_creation")
 * @ORM\Entity(repositoryClass="App\Repository\Wonder\CreationRepository")
 * @LadbAssert\BodyBlocks()
 */
class Creation extends AbstractWonder implements BlockBodiedInterface, InspirableInterface, FeedbackableInterface, SpotlightableInterface {

	use BlockBodiedTrait;
	use InspirableTrait, FeedbackableTrait, SpotlightableTrait;

	const CLASS_NAME = 'App\Entity\Wonder\Creation';
	const TYPE = 100;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=5)
	 */
	protected $pictures;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false, name="bodyExtract")
	 */
	private $bodyExtract;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true, onDelete="cascade")})
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1)
	 */
	private $bodyBlocks;

	/**
	 * @ORM\Column(type="integer", name="body_block_picture_count")
	 */
	private $bodyBlockPictureCount = 0;

	/**
	 * @ORM\Column(type="integer", name="body_block_video_count")
	 */
	private $bodyBlockVideoCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Input\Wood", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_wood")
	 */
	private $woods;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Input\Tool", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_tool")
	 */
	private $tools;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Input\Finish", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_finish")
	 */
	private $finishes;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Input\Hardware", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_hardware")
	 */
	private $hardwares;

	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\Core\Spotlight", cascade={"remove"})
	 * @ORM\JoinColumn(name="spotlight_id", referencedColumnName="id")
	 */
	private $spotlight = null;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_tag")
	 */
	protected $tags;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Referer\Referral", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_referral", inverseJoinColumns={@ORM\JoinColumn(name="referral_id", referencedColumnName="id", unique=true)})
	 * @ORM\OrderBy({"accessCount" = "DESC"})
	 */
	protected $referrals;

	/**
	 * @ORM\Column(type="integer", name="feedback_count")
	 */
	private $feedbackCount = 0;

	/**
	 * @ORM\Column(type="integer", name="rebound_count")
	 */
	private $reboundCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Creation", mappedBy="inspirations")
	 */
	private $rebounds;

	/**
	 * @ORM\Column(type="integer", name="inspiration_count")
	 */
	private $inspirationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Creation", inversedBy="rebounds", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_inspiration",
	 *      	joinColumns={ @ORM\JoinColumn(name="creation_id", referencedColumnName="id") },
	 *      	inverseJoinColumns={ @ORM\JoinColumn(name="rebound_creation_id", referencedColumnName="id") }
	 *      )
	 * @Assert\Count(min=0, max=4)
	 */
	private $inspirations;

	/**
	 * @ORM\Column(type="integer", name="question_count")
	 */
	private $questionCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Qa\Question", inversedBy="creations", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_question")
	 * @Assert\Count(min=0, max=4)
	 */
	private $questions;

	/**
	 * @ORM\Column(type="integer", name="plan_count")
	 */
	private $planCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Plan", inversedBy="creations", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_plan")
	 * @Assert\Count(min=0, max=4)
	 */
	private $plans;

	/**
	 * @ORM\Column(type="integer", name="howto_count")
	 */
	private $howtoCount = 0;

    /**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Howto\Howto", inversedBy="creations", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_howto")
	 * @Assert\Count(min=0, max=4)
	 */
	private $howtos;

	/**
	 * @ORM\Column(type="integer", name="workflow_count")
	 */
	private $workflowCount = 0;

    /**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Workflow\Workflow", inversedBy="creations", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_workflow")
	 * @Assert\Count(min=0, max=4)
	 */
	private $workflows;

	/**
	 * @ORM\Column(type="integer", name="provider_count")
	 */
	private $providerCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Provider", inversedBy="creations", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_provider")
	 * @Assert\Count(min=0, max=10)
	 */
	private $providers;

	/**
	 * @ORM\Column(type="integer", name="school_count")
	 */
	private $schoolCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\School", inversedBy="creations", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_creation_school")
	 * @Assert\Count(min=0, max=4)
	 */
	private $schools;

	/////

	public function __construct() {
		parent::__construct();
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->woods = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tools = new \Doctrine\Common\Collections\ArrayCollection();
		$this->finishes = new \Doctrine\Common\Collections\ArrayCollection();
		$this->hardwares = new \Doctrine\Common\Collections\ArrayCollection();
		$this->inspirations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->questions = new \Doctrine\Common\Collections\ArrayCollection();
		$this->plans = new \Doctrine\Common\Collections\ArrayCollection();
		$this->howtos = new \Doctrine\Common\Collections\ArrayCollection();
		$this->workflows = new \Doctrine\Common\Collections\ArrayCollection();
		$this->providers = new \Doctrine\Common\Collections\ArrayCollection();
		$this->schools = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Type /////

	public function getType() {
		return Creation::TYPE;
	}

	// Woods /////

	public function addWood(\App\Entity\Input\Wood $wood) {
        if (!$this->woods->contains($wood)) {
		    $this->woods[] = $wood;
        }
		return $this;
	}

	public function removeWood(\App\Entity\Input\Wood $wood) {
		$this->woods->removeElement($wood);
	}

	public function getWoods() {
		return $this->woods;
	}

	// Tools /////

	public function addTool(\App\Entity\Input\Tool $tool) {
		if (!$this->tools->contains($tool)) {
			$this->tools[] = $tool;
		}
		return $this;
	}

	public function removeTool(\App\Entity\Input\Tool $tool) {
		$this->tools->removeElement($tool);
	}

	public function getTools() {
		return $this->tools;
	}

	// Finishes /////

	public function addFinish(\App\Entity\Input\Finish $finish) {
		if (!$this->finishes->contains($finish)) {
			$this->finishes[] = $finish;
		}
		return $this;
	}

	public function removeFinish(\App\Entity\Input\Finish $finish) {
		$this->finishes->removeElement($finish);
	}

	public function getFinishes() {
		return $this->finishes;
	}

	// Hardwares /////

	public function addHardware(\App\Entity\Input\Hardware $hardware) {
		if (!$this->hardwares->contains($hardware)) {
			$this->hardwares[] = $hardware;
		}
		return $this;
	}

	public function removeHardware(\App\Entity\Input\Hardware $hardware) {
		$this->hardwares->removeElement($hardware);
	}

	public function getHardwares() {
		return $this->hardwares;
	}

	// LinkedEntities /////

	public function getLinkedEntities() {
		return array_merge(
			$this->inspirations->getValues(),
			$this->questions->getValues(),
			$this->plans->getValues(),
			$this->howtos->getValues(),
			$this->workflows->getValues(),
			$this->providers->getValues(),
			$this->schools->getValues()
		);
	}

	// QuestionCount /////

	public function incrementQuestionCount($by = 1) {
		return $this->questionCount += intval($by);
	}

	public function getQuestionCount() {
		return $this->questionCount;
	}

	// Questions /////

	public function addQuestion(\App\Entity\Qa\Question $question) {
		if (!$this->questions->contains($question)) {
			$this->questions[] = $question;
			$this->questionCount = count($this->questions);
			if (!$this->getIsDraft()) {
				$question->incrementCreationCount();
			}
		}
		return $this;
	}

	public function removeQuestion(\App\Entity\Qa\Question $question) {
		if ($this->questions->removeElement($question)) {
			$this->questionCount = count($this->questions);
			if (!$this->getIsDraft()) {
				$question->incrementCreationCount(-1);
			}
		}
	}

	public function getQuestions() {
		return $this->questions;
	}

	// PlanCount /////

	public function incrementPlanCount($by = 1) {
		return $this->planCount += intval($by);
	}

	public function getPlanCount() {
		return $this->planCount;
	}

	// Plans /////

	public function addPlan(\App\Entity\Wonder\Plan $plan) {
		if (!$this->plans->contains($plan)) {
			$this->plans[] = $plan;
			$this->planCount = count($this->plans);
			if (!$this->getIsDraft()) {
				$plan->incrementCreationCount();
			}
		}
		return $this;
	}

	public function removePlan(\App\Entity\Wonder\Plan $plan) {
		if ($this->plans->removeElement($plan)) {
			$this->planCount = count($this->plans);
			if (!$this->getIsDraft()) {
				$plan->incrementCreationCount(-1);
			}
		}
	}

	public function getPlans() {
		return $this->plans;
	}

	// HowtoCount /////

	public function incrementHowtoCount($by = 1) {
		return $this->howtoCount += intval($by);
	}

	public function getHowtoCount() {
		return $this->howtoCount;
	}

	// Howtos /////

	public function addHowto(\App\Entity\Howto\Howto $howto) {
		if (!$this->howtos->contains($howto)) {
			$this->howtos[] = $howto;
			$this->howtoCount = count($this->howtos);
			if (!$this->getIsDraft()) {
				$howto->incrementCreationCount();
			}
		}
		return $this;
	}

	public function removeHowto(\App\Entity\Howto\Howto $howto) {
		if ($this->howtos->removeElement($howto)) {
			$this->howtoCount = count($this->howtos);
			if (!$this->getIsDraft()) {
				$howto->incrementCreationCount(-1);
			}
		}
	}

	public function getHowtos() {
		return $this->howtos;
	}

	// WorkflowCount /////

	public function incrementWorkflowCount($by = 1) {
		return $this->workflowCount += intval($by);
	}

	public function getWorkflowCount() {
		return $this->workflowCount;
	}

	// Workflows /////

	public function addWorkflow(\App\Entity\Workflow\Workflow $workflow) {
		if (!$this->workflows->contains($workflow)) {
			$this->workflows[] = $workflow;
			$this->workflowCount = count($this->workflows);
			if (!$this->getIsDraft()) {
				$workflow->incrementCreationCount();
			}
		}
		return $this;
	}

	public function removeWorkflow(\App\Entity\Workflow\Workflow $workflow) {
		if ($this->workflows->removeElement($workflow)) {
			$this->workflowCount = count($this->workflows);
			if (!$this->getIsDraft()) {
				$workflow->incrementCreationCount(-1);
			}
		}
	}

	public function getWorkflows() {
		return $this->workflows;
	}

	// ProviderCount /////

	public function incrementProviderCount($by = 1) {
		return $this->providerCount += intval($by);
	}

	public function getProviderCount() {
		return $this->providerCount;
	}

	// Providers /////

	public function addProvider(\App\Entity\Knowledge\Provider $provider) {
		if (!$this->providers->contains($provider)) {
			$this->providers[] = $provider;
			$this->providerCount = count($this->providers);
			if (!$this->getIsDraft()) {
				$provider->incrementCreationCount();
			}
		}
		return $this;
	}

	public function removeProvider(\App\Entity\Knowledge\Provider $provider) {
		if ($this->providers->removeElement($provider)) {
			$this->providerCount = count($this->providers);
			if (!$this->getIsDraft()) {
				$provider->incrementCreationCount(-1);
			}
		}
	}

	public function getProviders() {
		return $this->providers;
	}

	// SchoolCount /////

	public function incrementSchoolCount($by = 1) {
		return $this->schoolCount += intval($by);
	}

	public function getSchoolCount() {
		return $this->schoolCount;
	}

	// Schools /////

	public function addSchool(\App\Entity\Knowledge\School $school) {
		if (!$this->schools->contains($school)) {
			$this->schools[] = $school;
			$this->schoolCount = count($this->schools);
			if (!$this->getIsDraft()) {
				$school->incrementCreationCount();
			}
		}
		return $this;
	}

	public function removeSchool(\App\Entity\Knowledge\School $school) {
		if ($this->schools->removeElement($school)) {
			$this->schoolCount = count($this->schools);
			if (!$this->getIsDraft()) {
				$school->incrementCreationCount(-1);
			}
		}
	}

	public function getSchools() {
		return $this->schools;
	}

}