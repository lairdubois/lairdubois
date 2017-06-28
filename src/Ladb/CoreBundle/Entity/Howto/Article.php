<?php

namespace Ladb\CoreBundle\Entity\Howto;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\BasicEmbeddableTrait;
use Ladb\CoreBundle\Model\BlockBodiedTrait;
use Ladb\CoreBundle\Model\TitledTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\BasicEmbeddableInterface;
use Ladb\CoreBundle\Model\WatchableChildInterface;
use Ladb\CoreBundle\Model\ChildInterface;

/**
 * @ORM\Table("tbl_howto_article")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Howto\ArticleRepository")
 * @LadbAssert\ArticleBody()
 * @LadbAssert\BodyBlocks()
 */
class Article extends AbstractPublication implements AuthoredInterface, TitledInterface, BlockBodiedInterface, WatchableChildInterface, BasicEmbeddableInterface, ChildInterface {

	use TitledTrait, BlockBodiedTrait;
	use BasicEmbeddableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Howto\Article';
	const TYPE = 107;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Howto\Howto", inversedBy="articles")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $howto;

	/**
	 * @ORM\Column(type="string", length=100)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=4)
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’ʼ#,.:%?!-]+$/", message="default.title.regex")
	 * @ladbAssert\UpperCaseRatio()
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
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Block\AbstractBlock", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_howto_article_body_block", inverseJoinColumns={@ORM\JoinColumn(name="block_id", referencedColumnName="id", unique=true)})
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
	 * @ORM\Column(type="integer")
	 */
	private $sortIndex = 0;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="sticker_id", nullable=true)
	 */
	private $sticker;

	/////

	public function __construct() {
		$this->bodyBlocks = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_FOLLOWER | self::NOTIFICATION_STRATEGY_WATCH;
	}

	// Type /////

	public function getType() {
		return Article::TYPE;
	}

	// Howto /////

    public function setHowto(\Ladb\CoreBundle\Entity\Howto\Howto $howto = null) {
        $this->howto = $howto;
        return $this;
    }

    public function getHowto() {
        return $this->howto;
    }

	// User /////

	public function getUser() {
		if (is_null($this->howto)) {
			return null;
		}
		return $this->howto->getUser();
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

	// MainPicture /////

	public function getMainPicture() {
		foreach ($this->getBodyBlocks() as $bodyBlock) {
			if ($bodyBlock instanceof Gallery) {
				$pictures = $bodyBlock->getPictures();
				if ($pictures->count() > 0) {
					return $pictures->first();
				}
			}
		}
		return null;
	}

	// SortIndex /////

	public function setSortIndex($sortIndex) {
		$this->sortIndex = $sortIndex;
		return $this;
	}

	public function getSortIndex() {
		return $this->sortIndex;
	}

	// ParentEntityType /////

	public function getParentEntityType() {
		return $this->getHowto()->getType();
	}

	// ParentEntityId /////

	public function getParentEntityId() {
		return $this->getHowto()->getId();
	}

	// ParentEntity /////

	public function getParentEntity() {
		return $this->getHowto();
	}

}