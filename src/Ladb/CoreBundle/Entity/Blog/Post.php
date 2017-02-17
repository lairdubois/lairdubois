<?php

namespace Ladb\CoreBundle\Entity\Blog;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
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
 * @ORM\Table("tbl_blog_post")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Blog\PostRepository")
 * @LadbAssert\BodyBlocks()
 */
class Post extends AbstractAuthoredPublication implements IndexableInterface, TitledInterface, PicturedInterface, BlockBodiedInterface, TaggableInterface, ViewableInterface, ScrapableInterface, LikableInterface, WatchableInterface, CommentableInterface, ReportableInterface, ExplorableInterface {

	const CLASS_NAME = 'LadbCoreBundle:Blog\Post';
	const TYPE = 108;

	const HIGHLIGHT_LEVEL_NONE = 0;
	const HIGHLIGHT_LEVEL_USER_ONLY = 1;
	const HIGHLIGHT_LEVEL_ALL = 2;

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
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $body;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_blog_post_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true)})
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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=false, name="main_picture_id")
	 * @Assert\NotBlank()
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Picture")
	 */
	private $mainPicture;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_blog_post_tag")
	 * @Assert\Count(min=2)
	 */
	private $tags;

	/**
	 * @ORM\Column(type="smallint", name="highlight_level")
	 */
	private $highlightLevel = Post::HIGHLIGHT_LEVEL_NONE;

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
	 * @ORM\Column(type="integer", name="view_count")
	 */
	private $viewCount = 0;

	/////

	private $isShown = true;

	/////

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
		$this->tags = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Type /////

	public function getType() {
		return Post::TYPE;
	}

	// IsIndexable /////

	public function isIndexable() {
		return $this->isDraft !== true;
	}

	// IsViewable /////

	public function getIsViewable() {
		return $this->isDraft !== true;
	}

	// IsShown /////

	public function setIsShown($isShown) {
		$this->isShown = $isShown;
	}

	public function getIsShown() {
		return $this->isShown;
	}

	// IsScrapable /////

	public function getIsScrapable() {
		return $this->getIsViewable();
	}

	// Title /////

	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	public function getTitle() {
		return $this->title;
	}

	// Slug /////

	public function setSlug($slug) {
		$this->slug = $slug;
		return $this;
	}

	public function getSlug() {
		return $this->slug;
	}

	public function getSluggedId() {
		return $this->id.'-'.$this->slug;
	}

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

	// BodyExtract /////

	public function getBodyExtract() {
		$firstBlock = $this->bodyBlocks->first();
		if ($firstBlock instanceof \Ladb\CoreBundle\Entity\Block\Text) {
			return $firstBlock->getHtmlBody();
		}
		return '';
	}

	// BodyBlocks /////

	public function addBodyBlock(\Ladb\CoreBundle\Entity\Block\AbstractBlock $bodyBlock) {
		if (!$this->bodyBlocks->contains($bodyBlock)) {
			$this->bodyBlocks[] = $bodyBlock;
		}
		return $this;
	}

	public function removeBodyBlock(\Ladb\CoreBundle\Entity\Block\AbstractBlock $bodyBlock) {
		$this->bodyBlocks->removeElement($bodyBlock);
	}

	public function getBodyBlocks() {
		return $this->bodyBlocks;
	}

	public function resetBodyBlocks() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// BodyBlockPictureCount /////

	public function setBodyBlockPictureCount($bodyBlockPictureCount) {
		$this->bodyBlockPictureCount = $bodyBlockPictureCount;
		return $this;
	}

	public function getBodyBlockPictureCount() {
		return $this->bodyBlockPictureCount;
	}

	// BodyBlockVideoCount /////

	public function setBodyBlockVideoCount($bodyBlockVideoCount) {
		$this->bodyBlockVideoCount = $bodyBlockVideoCount;
		return $this;
	}

	public function getBodyBlockVideoCount() {
		return $this->bodyBlockVideoCount;
	}

	// MainPicture /////

	public function setMainPicture(\Ladb\CoreBundle\Entity\Picture $mainPicture) {
		$this->mainPicture = $mainPicture;
		return $this;
	}

	public function getMainPicture() {
		return $this->mainPicture;
	}

	// Tags /////

	public function addTag(\Ladb\CoreBundle\Entity\Tag $tag) {
		$this->tags[] = $tag;
		return $this;
	}

	public function removeTag(\Ladb\CoreBundle\Entity\Tag $tag) {
		$this->tags->removeElement($tag);
	}

	public function getTags() {
		return $this->tags;
	}

	// HighlightLevel

	public function setHighlightLevel($highlightLevel) {
		$this->highlightLevel = $highlightLevel;
		return $this;
	}

	public function getHighlightLevel() {
		return $this->highlightLevel;
	}

	// LikeCount /////

	public function incrementLikeCount($by = 1) {
		return $this->likeCount += intval($by);
	}

	public function setLikeCount($likeCount) {
		$this->likeCount = $likeCount;
		return $this;
	}

	public function getLikeCount() {
		return $this->likeCount;
	}

	// WatchCount /////

	public function incrementWatchCount($by = 1) {
		return $this->watchCount += intval($by);
	}

	public function setWatchCount($watchCount) {
		$this->watchCount = $watchCount;
		return $this;
	}

	public function getWatchCount() {
		return $this->watchCount;
	}

	// CommentCount /////

	public function incrementCommentCount($by = 1) {
		return $this->commentCount += intval($by);
	}

	public function setCommentCount($commentCount) {
		$this->commentCount = $commentCount;

		return $this;
	}

	public function getCommentCount() {
		return $this->commentCount;
	}

	// ViewCount /////

	public function incrementViewCount($by = 1) {
		return $this->viewCount += intval($by);
	}

	public function setViewCount($viewCount) {
		$this->viewCount = $viewCount;
		return $this;
	}

	public function getViewCount() {
		return $this->viewCount;
	}

}