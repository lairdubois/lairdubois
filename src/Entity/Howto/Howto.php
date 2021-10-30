<?php

namespace App\Entity\Howto;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Model\SpotlightableInterface;
use App\Model\SpotlightableTrait;
use App\Model\StripableInterface;
use App\Model\StripableTrait;
use App\Entity\AbstractDraftableAuthoredPublication;
use App\Model\LinkedToInterface;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
use App\Model\HtmlBodiedTrait;
use App\Model\CommentableTrait;
use App\Model\EmbeddableTrait;
use App\Model\IndexableTrait;
use App\Model\LicensedTrait;
use App\Model\LikableTrait;
use App\Model\PicturedTrait;
use App\Model\ScrapableTrait;
use App\Model\SitemapableInterface;
use App\Model\SitemapableTrait;
use App\Model\TaggableTrait;
use App\Model\TitledTrait;
use App\Model\ViewableTrait;
use App\Model\WatchableTrait;
use App\Model\IndexableInterface;
use App\Model\TitledInterface;
use App\Model\EmbeddableInterface;
use App\Model\PicturedInterface;
use App\Model\HtmlBodiedInterface;
use App\Model\LicensedInterface;
use App\Model\ViewableInterface;
use App\Model\LikableInterface;
use App\Model\WatchableInterface;
use App\Model\CommentableInterface;
use App\Model\ReportableInterface;
use App\Model\ExplorableInterface;
use App\Model\TaggableInterface;
use App\Model\ScrapableInterface;

/**
 * @ORM\Table("tbl_howto")
 * @ORM\Entity(repositoryClass="App\Repository\Howto\HowtoRepository")
 */
class Howto extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, HtmlBodiedInterface, LicensedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, ReportableInterface, ExplorableInterface, EmbeddableInterface, StripableInterface, LinkedToInterface, SpotlightableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, HtmlBodiedTrait, LicensedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait, EmbeddableTrait, StripableTrait, SpotlightableTrait;

	const TYPE = 106;

	const KIND_NONE = 0;
	const KIND_TUTORIAL = 1;
	const KIND_TECHNICAL = 2;
	const KIND_DOCUMENTATION = 3;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=4, max=100)
	 */
	private $title;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $kind = Howto::KIND_NONE;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=2000)
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false, name="htmlBody")
	 */
	private $htmlBody;

	/**
	 * @ORM\Column(type="integer", name="body_block_video_count")
	 */
	private $bodyBlockVideoCount = 0;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="main_picture_id", nullable=false)
	 * @Assert\Type(type="App\Entity\Core\Picture")
	 * @Assert\NotBlank()
	 */
	private $mainPicture;

	/**
	 * @ORM\Column(type="boolean", name="is_work_in_progress")
	 */
	private $isWorkInProgress = false;

	/**
	 * @ORM\Column(name="draft_article_count", type="integer")
	 */
	private $draftArticleCount = 0;

	/**
	 * @ORM\Column(name="published_article_count", type="integer")
	 */
	private $publishedArticleCount = 0;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Howto\Article", mappedBy="howto", cascade={"all"})
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 */
	private $articles;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_howto_tag")
	 */
	private $tags;

	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\Core\License", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, name="license_id")
	 * @Assert\Type(type="App\Entity\Core\License")
	 */
	private $license;

	/**
	 * @ORM\Column(type="integer", name="like_count")
	 */
	private $likeCount = 0;

	/**
	 * @ORM\Column(type="integer", name="watch_count")
	 */
	private $watchCount = 0;

	/**
	 * @ORM\Column(type="integer", name="comment_count")
	 */
	private $commentCount = 0;

	/**
	 * @ORM\Column(type="integer", name="private_collection_count")
	 */
	private $privateCollectionCount = 0;

	/**
	 * @ORM\Column(type="integer", name="public_collection_count")
	 */
	private $publicCollectionCount = 0;

	/**
	 * @ORM\Column(type="integer", name="view_count")
	 */
	private $viewCount = 0;

	/**
	 * @ORM\OneToOne(targetEntity="App\Entity\Core\Spotlight", cascade={"remove"})
	 * @ORM\JoinColumn(name="spotlight_id", referencedColumnName="id")
	 */
	private $spotlight = null;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="sticker_id", nullable=true)
	 */
	private $sticker;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="strip_id", nullable=true)
	 */
	private $strip;

	/**
	 * @ORM\Column(type="integer", name="referral_count")
	 */
	private $referralCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Referer\Referral", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_howto_referral", inverseJoinColumns={@ORM\JoinColumn(name="referral_id", referencedColumnName="id", unique=true)})
	 * @ORM\OrderBy({"accessCount" = "DESC"})
	 */
	protected $referrals;

	/**
	 * @ORM\Column(type="integer", name="question_count")
	 */
	private $questionCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Qa\Question", inversedBy="howtos", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_howto_question")
	 * @Assert\Count(min=0, max=4)
	 */
	private $questions;

	/**
	 * @ORM\Column(type="integer", name="creation_count")
	 */
	private $creationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Creation", mappedBy="howtos")
	 */
	private $creations;

	/**
	 * @ORM\Column(type="integer", name="workshop_count")
	 */
	private $workshopCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Workshop", mappedBy="howtos")
	 */
	private $workshops;

	/**
	 * @ORM\Column(type="integer", name="plan_count")
	 */
	private $planCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Plan", inversedBy="howtos", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_howto_plan")
	 * @Assert\Count(min=0, max=4)
	 */
	private $plans;

	/**
	 * @ORM\Column(type="integer", name="workflow_count")
	 */
	private $workflowCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Workflow\Workflow", inversedBy="howtos", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_howto_workflow")
	 * @Assert\Count(min=0, max=4)
	 */
	private $workflows;

	/**
	 * @ORM\Column(type="integer", name="provider_count")
	 */
	private $providerCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\Provider", inversedBy="howtos", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_howto_provider")
	 * @Assert\Count(min=0, max=10)
	 */
	private $providers;

	/**
	 * @ORM\Column(type="integer", name="school_count")
	 */
	private $schoolCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Knowledge\School", inversedBy="howtos", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_howto_school")
	 * @Assert\Count(min=0, max=4)
	 */
	private $schools;

	/////

	public function __construct() {
		$this->articles = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
		$this->referrals = new \Doctrine\Common\Collections\ArrayCollection();
		$this->questions = new \Doctrine\Common\Collections\ArrayCollection();
		$this->creations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->workshops = new \Doctrine\Common\Collections\ArrayCollection();
		$this->plans = new \Doctrine\Common\Collections\ArrayCollection();
		$this->workflows = new \Doctrine\Common\Collections\ArrayCollection();
		$this->providers = new \Doctrine\Common\Collections\ArrayCollection();
		$this->schools = new \Doctrine\Common\Collections\ArrayCollection();
	}

    /////

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER;
	}

	// SubPublications /////

	public function getSubPublications() {
		return $this->getArticles();
	}

	// Type /////

    public function getType() {
		return Howto::TYPE;
	}

	// Kind /////

	public function getKind() {
		return $this->kind;
	}

	public function setKind($kind) {
		$this->kind = $kind;
		return $this;
	}

	// BodyBlockVideoCount /////

	public function setBodyBlockVideoCount($bodyBlockVideoCount) {
		$this->bodyBlockVideoCount = $bodyBlockVideoCount;
		return $this;
	}

	public function getBodyBlockVideoCount() {
		return $this->bodyBlockVideoCount;
	}

	// WorkInProgress /////

	public function setIsWorkInProgress($isWorkInProgress) {
		$this->isWorkInProgress = $isWorkInProgress;
		return $this;
	}

	public function  getIsWorkInProgress() {
		return $this->isWorkInProgress;
	}

	// DraftArticleCount /////

	public function incrementDraftArticleCount($by = 1) {
		return $this->draftArticleCount += intval($by);
	}

	public function getDraftArticleCount() {
		return $this->draftArticleCount;
	}

	// PublishedArticleCount /////

	public function incrementPublishedArticleCount($by = 1) {
		return $this->publishedArticleCount += intval($by);
	}

	public function getPublishedArticleCount() {
		return $this->publishedArticleCount;
	}

	// Articles /////

	public function addArticle(\App\Entity\Howto\Article $article) {
		if (!$this->articles->contains($article)) {
			$this->articles[] = $article;
			$article->setHowto($this);
		}
		return $this;
	}

	public function removeArticle(\App\Entity\Howto\Article $article) {
		if ($this->articles->removeElement($article)) {
			$article->setHowto(null);
		}
	}

	public function getArticles() {
		return $this->articles;
	}

	public function resetArticles() {
		$this->articles = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// ArticleMaxSortIndex /////

	public function getArticleMaxSortIndex() {
		$maxSortIndex = -1;
		foreach ($this->getArticles() as $article) {
			$maxSortIndex = max($maxSortIndex, $article->getSortIndex());
		}
		return $maxSortIndex;
	}

	// LinkedEntities /////

	public function getLinkedEntities() {
		return array_merge(
			$this->questions->getValues(),
			$this->plans->getValues(),
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
				$question->incrementHowtoCount();
			}
		}
		return $this;
	}

	public function removeQuestion(\App\Entity\Qa\Question $question) {
		if ($this->questions->removeElement($question)) {
			$this->questionCount = count($this->questions);
			if (!$this->getIsDraft()) {
				$question->incrementHowtoCount(-1);
			}
		}
	}

	public function getQuestions() {
		return $this->questions;
	}

	// CreationCount /////

	public function incrementCreationCount($by = 1) {
		return $this->creationCount += intval($by);
	}

	public function setCreationCount($creationCount) {
		$this->creationCount = $creationCount;
		return $this;
	}

	public function getCreationCount() {
		return $this->creationCount;
	}

	// Creations /////

	public function getCreations() {
		return $this->creations;
	}

	// WorkshopCount /////

	public function incrementWorkshopCount($by = 1) {
		return $this->workshopCount += intval($by);
	}

	public function setWorkshopCount($workshopCount) {
		$this->workshopCount = $workshopCount;
		return $this;
	}

	public function getWorkshopCount() {
		return $this->workshopCount;
	}

	// Workshops /////

	public function getWorkshops() {
		return $this->workshops;
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
				$plan->incrementHowtoCount();
			}
		}
		return $this;
	}

	public function removePlan(\App\Entity\Wonder\Plan $plan) {
		if ($this->plans->removeElement($plan)) {
			$this->planCount = count($this->plans);
			if (!$this->getIsDraft()) {
				$plan->incrementHowtoCount(-1);
			}
		}
	}

	public function getPlans() {
		return $this->plans;
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
				$workflow->incrementHowtoCount();
			}
		}
		return $this;
	}

	public function removeWorkflow(\App\Entity\Workflow\Workflow $workflow) {
		if ($this->workflows->removeElement($workflow)) {
			$this->workflowCount = count($this->workflows);
			if (!$this->getIsDraft()) {
				$workflow->incrementHowtoCount(-1);
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
				$provider->incrementHowtoCount();
			}
		}
		return $this;
	}

	public function removeProvider(\App\Entity\Knowledge\Provider $provider) {
		if ($this->providers->removeElement($provider)) {
			$this->providerCount = count($this->providers);
			if (!$this->getIsDraft()) {
				$provider->incrementHowtoCount(-1);
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
				$school->incrementHowtoCount();
			}
		}
		return $this;
	}

	public function removeSchool(\App\Entity\Knowledge\School $school) {
		if ($this->schools->removeElement($school)) {
			$this->schoolCount = count($this->schools);
			if (!$this->getIsDraft()) {
				$school->incrementHowtoCount(-1);
			}
		}
	}

	public function getSchools() {
		return $this->schools;
	}

}