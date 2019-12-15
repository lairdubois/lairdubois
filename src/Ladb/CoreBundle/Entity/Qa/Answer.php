<?php

namespace Ladb\CoreBundle\Entity\Qa;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\BasicTimestampableTrait;
use Ladb\CoreBundle\Model\MentionSourceInterface;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\AuthoredTrait;
use Ladb\CoreBundle\Model\BlockBodiedTrait;
use Ladb\CoreBundle\Model\CommentableTrait;
use Ladb\CoreBundle\Model\TypableInterface;
use Ladb\CoreBundle\Model\VotableInterface;
use Ladb\CoreBundle\Model\VotableTrait;
use Ladb\CoreBundle\Model\WatchableChildInterface;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\CommentableInterface;

/**
 * @ORM\Table("tbl_qa_answer")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Qa\AnswerRepository")
 * @LadbAssert\BodyBlocks()
 * @LadbAssert\ValidAnswer()
 */
class Answer implements TypableInterface, AuthoredInterface, BlockBodiedInterface, CommentableInterface, VotableInterface, WatchableChildInterface, MentionSourceInterface {

	use BasicTimestampableTrait, AuthoredTrait, BlockBodiedTrait;
	use CommentableTrait, VotableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Qa\Answer';
	const TYPE = 114;

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	private $createdAt;

	/**
	 * @ORM\Column(name="updated_at", type="datetime")
	 */
	private $updatedAt;

	/**
	 * @ORM\Column(name="is_best_answer", type="boolean")
	 */
	protected $isBestAnswer = false;

	/**
	 * @ORM\Column(name="parent_entity_type", type="smallint", nullable=false)
	 */
	protected $parentEntityType;

	/**
	 * @ORM\Column(name="parent_entity_id", type="integer", nullable=false)
	 */
	protected $parentEntityId;

	/**
	 * @ORM\Column(name="parent_entity_field", type="string", length=20, nullable=false)
	 */
	protected $parentEntityField;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Qa\Question", inversedBy="answers")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $question;

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
	 * @ORM\JoinTable(name="tbl_qa_answer_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true, onDelete="cascade")})
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
	 * @ORM\Column(type="integer", name="comment_count")
	 */
	private $commentCount = 0;

	/**
	 * @ORM\Column(type="integer", name="positive_vote_score")
	 */
	private $positiveVoteScore = 0;

	/**
	 * @ORM\Column(type="integer", name="negative_vote_score")
	 */
	private $negativeVoteScore = 0;

	/**
	 * @ORM\Column(type="integer", name="vote_score")
	 */
	private $voteScore = 0;

	/**
	 * @ORM\Column(type="integer", name="vote_count")
	 */
	private $voteCount = 0;

	/////

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Type /////

	public function getType() {
		return Answer::TYPE;
	}

	// Id /////

	public function getId() {
		return $this->id;
	}

	// IsBestAnswer /////

	public function getIsBestAnswer() {
		return $this->isBestAnswer;
	}

	public function setIsBestAnswer($isBestAnswer) {
		$this->isBestAnswer = $isBestAnswer;
	}

	// Question /////

	public function getQuestion() {
		return $this->question;
	}

	public function setQuestion(\Ladb\CoreBundle\Entity\Qa\Question $question) {
		$this->question = $question;
		return $this;
	}

	// Title /////

	public function getTitle() {
		return mb_strimwidth($this->getBody(), 0, 50, '[...]');
	}

}