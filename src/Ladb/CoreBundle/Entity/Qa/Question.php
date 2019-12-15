<?php

namespace Ladb\CoreBundle\Entity\Qa;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Entity\AbstractDraftableAuthoredPublication;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Model\CollectionnableTrait;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\PicturedTrait;
use Ladb\CoreBundle\Model\SluggedInterface;
use Ladb\CoreBundle\Model\SluggedTrait;
use Ladb\CoreBundle\Model\VotableParentInterface;
use Ladb\CoreBundle\Model\VotableParentTrait;
use Ladb\CoreBundle\Model\BlockBodiedTrait;
use Ladb\CoreBundle\Model\CommentableTrait;
use Ladb\CoreBundle\Model\IndexableTrait;
use Ladb\CoreBundle\Model\LikableTrait;
use Ladb\CoreBundle\Model\ScrapableTrait;
use Ladb\CoreBundle\Model\SitemapableInterface;
use Ladb\CoreBundle\Model\SitemapableTrait;
use Ladb\CoreBundle\Model\TaggableTrait;
use Ladb\CoreBundle\Model\TitledTrait;
use Ladb\CoreBundle\Model\ViewableTrait;
use Ladb\CoreBundle\Model\WatchableTrait;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\TaggableInterface;
use Ladb\CoreBundle\Model\ExplorableInterface;
use Ladb\CoreBundle\Model\ScrapableInterface;

/**
 * @ORM\Table("tbl_qa_question")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Qa\QuestionRepository")
 * @LadbAssert\BodyBlocks()
 */
class Question extends AbstractDraftableAuthoredPublication implements TitledInterface, SluggedInterface, PicturedInterface, BlockBodiedInterface, IndexableInterface, SitemapableInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, CollectionnableInterface, VotableParentInterface, ReportableInterface, ExplorableInterface {

	use TitledTrait, SluggedTrait, PicturedTrait, BlockBodiedTrait;
	use IndexableTrait, SitemapableTrait, TaggableTrait, ViewableTrait, ScrapableTrait, LikableTrait, WatchableTrait, CommentableTrait, CollectionnableTrait, VotableParentTrait;

	const CLASS_NAME = 'LadbCoreBundle:Qa\Question';
	const TYPE = 113;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=4)
	 */
	private $title;

	/**
	 * @Gedmo\Slug(fields={"title"}, separator="-")
	 * @ORM\Column(type="string", length=100, unique=true)
	 */
	private $slug;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=true, name="main_picture_id")
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
	 */
	private $mainPicture;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $body;

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	private $bodyExtract;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Qa\Answer")
	 * @ORM\JoinColumn(nullable=true, name="best_answer_id")
	 */
	private $bestAnswer;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Qa\Answer", mappedBy="question", cascade={"all"})
	 * @ORM\OrderBy({"isBestAnswer" = "DESC", "voteScore" = "DESC", "createdAt" = "DESC"})
	 */
	private $answers;

	/**
	 * @ORM\Column(type="integer", name="creation_count")
	 */
	private $creationCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Creation", mappedBy="questions")
	 */
	private $creations;

	/**
	 * @ORM\Column(type="integer", name="howto_count")
	 */
	private $howtoCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Howto\Howto", mappedBy="questions")
	 */
	private $howtos;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_qa_question_tag")
	 * @Assert\Count(min=2)
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

	/////

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->answers = new \Doctrine\Common\Collections\ArrayCollection();
		$this->creations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->howtos = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
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

	public function setBestAnswer(\Ladb\CoreBundle\Entity\Qa\Answer $bestAnswer = null) {
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

	public function addAnswer(\Ladb\CoreBundle\Entity\Qa\Answer $answer) {
		if (!$this->answers->contains($answer)) {
			$this->answers[] = $answer;
			$answer->setQuestion($this);
		}
		return $this;
	}

	public function removeAnswer(\Ladb\CoreBundle\Entity\Qa\Answer $answer) {
		if ($this->answers->removeElement($answer)) {
			$answer->setQuestion(null);
		}
	}

	public function getAnswers() {
		return $this->answers;
	}

	// License /////

	public function getLicense() {
		return new \Ladb\CoreBundle\Entity\Core\License(true, true, true);
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