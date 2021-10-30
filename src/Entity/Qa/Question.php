<?php

namespace App\Entity\Qa;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Entity\AbstractDraftableAuthoredPublication;
use App\Model\CollectionnableInterface;
use App\Model\CollectionnableTrait;
use App\Model\PicturedInterface;
use App\Model\PicturedTrait;
use App\Model\SluggedInterface;
use App\Model\SluggedTrait;
use App\Model\VotableParentInterface;
use App\Model\VotableParentTrait;
use App\Model\BlockBodiedTrait;
use App\Model\CommentableTrait;
use App\Model\IndexableTrait;
use App\Model\LikableTrait;
use App\Model\ScrapableTrait;
use App\Model\SitemapableInterface;
use App\Model\SitemapableTrait;
use App\Model\TaggableTrait;
use App\Model\TitledTrait;
use App\Model\ViewableTrait;
use App\Model\WatchableTrait;
use App\Model\IndexableInterface;
use App\Model\TitledInterface;
use App\Model\BlockBodiedInterface;
use App\Model\ViewableInterface;
use App\Model\LikableInterface;
use App\Model\WatchableInterface;
use App\Model\CommentableInterface;
use App\Model\ReportableInterface;
use App\Model\TaggableInterface;
use App\Model\ExplorableInterface;
use App\Model\ScrapableInterface;

/**
 * @ORM\Table("tbl_qa_question")
 * @ORM\Entity(repositoryClass="App\Repository\Qa\QuestionRepository")
 * @LadbAssert\BodyBlocks()
 */
class Question extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, BlockBodiedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, VotableParentInterface, ReportableInterface, ExplorableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, BlockBodiedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait, VotableParentTrait;

	const TYPE = 113;

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
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=true, name="main_picture_id")
	 * @Assert\Type(type="App\Entity\Core\Picture")
	 */
	private $mainPicture;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $body;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false, name="bodyExtract")
	 */
	private $bodyExtract;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_qa_question_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true, onDelete="cascade")})
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
	 * @ORM\Column(type="integer", name="answer_count")
	 */
	private $answerCount = 0;

	/**
	 * @ORM\Column(type="integer", name="positive_answer_count")
	 */
	private $positiveAnswerCount = 0;

	/**
	 * @ORM\Column(type="integer", name="null_answer_count")
	 */
	private $nullAnswerCount = 0;

	/**
	 * @ORM\Column(type="integer", name="undetermined_answer_count")
	 */
	private $undeterminedAnswerCount = 0;

	/**
	 * @ORM\Column(type="integer", name="negative_answer_count")
	 */
	private $negativeAnswerCount = 0;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Qa\Answer")
	 * @ORM\JoinColumn(nullable=true, name="best_answer_id")
	 */
	private $bestAnswer;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Qa\Answer", mappedBy="question", cascade={"all"})
	 * @ORM\OrderBy({"isBestAnswer" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $answers;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_qa_question_tag")
	 */
	private $tags;

	/**
	 * @ORM\Column(type="integer", name="positive_vote_count")
	 */
	private $positiveVoteCount = 0;

	/**
	 * @ORM\Column(type="integer", name="negative_vote_count")
	 */
	private $negativeVoteCount = 0;

	/**
	 * @ORM\Column(type="integer", name="vote_count")
	 */
	private $voteCount = 0;

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
	 * @ORM\Column(type="integer", name="creation_count")
	 */
	private $creationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Creation", mappedBy="questions")
	 */
	private $creations;

	/**
	 * @ORM\Column(type="integer", name="plan_count")
	 */
	private $planCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Wonder\Plan", mappedBy="questions")
	 */
	private $plans;

	/**
	 * @ORM\Column(type="integer", name="howto_count")
	 */
	private $howtoCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="App\Entity\Howto\Howto", mappedBy="questions")
	 */
	private $howtos;

	/////

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->answers = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
		$this->creations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->plans = new \Doctrine\Common\Collections\ArrayCollection();
		$this->howtos = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER;
	}

	// Type /////

	public function getType() {
		return Question::TYPE;
	}

	// AnswerCount /////

	public function incrementAnswerCount($by = 1) {
		return $this->answerCount += intval($by);
	}

	public function getAnswerCount() {
		return $this->answerCount;
	}

	// PositiveAnswerCount /////

	public function getPositiveAnswerCount() {
		return $this->positiveAnswerCount;
	}

	public function setPositiveAnswerCount($positiveAnswerCount) {
		$this->positiveAnswerCount = $positiveAnswerCount;
		return $this;
	}

	// NullAnswerCount /////

	public function getNullAnswerCount() {
		return $this->nullAnswerCount;
	}

	public function setNullAnswerCount($nullAnswerCount) {
		$this->nullAnswerCount = $nullAnswerCount;
		return $this;
	}

	// UndeterminedAnswerCount /////

	public function getUndeterminedAnswerCount() {
		return $this->undeterminedAnswerCount;
	}

	public function setUndeterminedAnswerCount($undeterminedAnswerCount) {
		$this->undeterminedAnswerCount = $undeterminedAnswerCount;
		return $this;
	}

	// NegativeAnswerCount /////

	public function getNegativeAnswerCount() {
		return $this->negativeAnswerCount;
	}

	public function setNegativeAnswerCount($negativeAnswerCount) {
		$this->negativeAnswerCount = $negativeAnswerCount;
		return $this;
	}

	// BestAnswer /////

	public function getBestAnswer() {
		return $this->bestAnswer;
	}

	public function setBestAnswer(\App\Entity\Qa\Answer $bestAnswer = null) {
		if (!is_null($this->getBestAnswer())) {
			$this->getBestAnswer()->setIsBestAnswer(false);
		}
		$this->bestAnswer = $bestAnswer;
		if (!is_null($this->getBestAnswer())) {
			$this->getBestAnswer()->setIsBestAnswer(true);
		}
		return $this;
	}

	// Answers /////

	public function addAnswer(\App\Entity\Qa\Answer $answer) {
		if (!$this->answers->contains($answer)) {
			$this->answers[] = $answer;
			$answer->setQuestion($this);
		}
		return $this;
	}

	public function removeAnswer(\App\Entity\Qa\Answer $answer) {
		if ($this->answers->removeElement($answer)) {
			$answer->setQuestion(null);
		}
	}

	public function getAnswers() {
		return $this->answers;
	}

	// License /////

	public function getLicense() {
		return new \App\Entity\Core\License(true, true, true);
	}

	// CreationCount /////

	public function incrementCreationCount($by = 1) {
		return $this->creationCount += intval($by);
	}

	public function getCreationCount() {
		return $this->creationCount;
	}

	public function setCreationCount($creationCount) {
		$this->creationCount = $creationCount;
		return $this;
	}

	// Creations /////

	public function getCreations() {
		return $this->creations;
	}

	// PlanCount /////

	public function incrementPlanCount($by = 1) {
		return $this->planCount += intval($by);
	}

	public function getPlanCount() {
		return $this->planCount;
	}

	public function setPlanCount($planCount) {
		$this->planCount = $planCount;
		return $this;
	}

	// Plans /////

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

	public function setHowtoCount($howtoCount) {
		$this->howtoCount = $howtoCount;
		return $this;
	}

	// Howtos /////

	public function getHowtos() {
		return $this->howtos;
	}

}