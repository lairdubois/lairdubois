<?php

namespace Ladb\CoreBundle\Entity\Qa;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\BlockBodiedTrait;
use Ladb\CoreBundle\Model\CommentableTrait;
use Ladb\CoreBundle\Model\IndexableTrait;
use Ladb\CoreBundle\Model\LikableTrait;
use Ladb\CoreBundle\Model\PicturedTrait;
use Ladb\CoreBundle\Model\ScrapableTrait;
use Ladb\CoreBundle\Model\SitemapableInterface;
use Ladb\CoreBundle\Model\SitemapableTrait;
use Ladb\CoreBundle\Model\TaggableTrait;
use Ladb\CoreBundle\Model\TitledTrait;
use Ladb\CoreBundle\Model\ViewableTrait;
use Ladb\CoreBundle\Model\VotableInterface;
use Ladb\CoreBundle\Model\VotableTrait;
use Ladb\CoreBundle\Model\WatchableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\TaggableInterface;
use Ladb\CoreBundle\Model\ExplorableInterface;
use Ladb\CoreBundle\Model\ScrapableInterface;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;

/**
 * @ORM\Table("tbl_qa_answer")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Qa\AnswerRepository")
 * @LadbAssert\BodyBlocks()
 */
class Answer extends AbstractAuthoredPublication implements BlockBodiedInterface, CommentableInterface, VotableInterface {

	use BlockBodiedTrait;
	use CommentableTrait, VotableTrait;

	const CLASS_NAME = 'LadbCoreBundle:QA\Answer';
	const TYPE = 114;

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
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_qa_answer_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true)})
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

	// Question /////

	public function getQuestion() {
		return $this->question;
	}

	public function setQuestion(\Ladb\CoreBundle\Entity\Qa\Question $question) {
		$this->question = $question;
		return $this;
	}

}