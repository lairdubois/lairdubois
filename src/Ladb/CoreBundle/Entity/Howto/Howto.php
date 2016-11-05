<?php

namespace Ladb\CoreBundle\Entity\Howto;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\EmbeddableInterface;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\LicensedInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\ExplorableInterface;
use Ladb\CoreBundle\Model\TaggableInterface;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;

/**
 * @ORM\Table("tbl_howto")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Howto\HowtoRepository")
 */
class Howto extends AbstractAuthoredPublication implements IndexableInterface, TitledInterface, PicturedInterface, BodiedInterface, TaggableInterface, LicensedInterface, ViewableInterface, LikableInterface, WatchableInterface, CommentableInterface, ReportableInterface, ExplorableInterface, EmbeddableInterface {

	const CLASS_NAME = 'LadbCoreBundle:Howto\Howto';
    const TYPE = 106;

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
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=2000)
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $htmlBody;

	/**
	 * @ORM\Column(type="integer", name="body_block_video_count")
	 */
	private $bodyBlockVideoCount = 0;

	/**
     * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
     * @ORM\JoinColumn(name="main_picture_id", nullable=false)
     * @Assert\Type(type="Ladb\CoreBundle\Entity\Picture")
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
     * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Howto\Article", mappedBy="howto", cascade={"all"})
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
     */
    private $articles;

	/**
	 * @ORM\Column(type="integer", name="plan_count")
	 */
	private $planCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Plan", inversedBy="howtos", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_wonder_howto_plan")
	 * @Assert\Count(min=0, max=4)
	 */
	private $plans;

	/**
	 * @ORM\Column(type="integer", name="creation_count")
	 */
	private $creationCount = 0;

	/**
     * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Creation", mappedBy="howtos")
     */
    private $creations;

	/**
	 * @ORM\Column(type="integer", name="workshop_count")
	 */
	private $workshopCount = 0;

	/**
     * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Wonder\Workshop", mappedBy="howtos")
     */
    private $workshops;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Tag", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_howto_tag")
	 * @Assert\Count(min=2)
	 */
	private $tags;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\License", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, name="license_id")
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\License")
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
     * @ORM\Column(type="integer", name="view_count")
     */
    private $viewCount = 0;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\Spotlight", cascade={"remove"})
	 * @ORM\JoinColumn(name="spotlight_id", referencedColumnName="id")
	 */
	private $spotlight = null;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="sticker_id", nullable=true)
	 */
	private $sticker;

	/**
	 * @ORM\Column(type="integer", name="referral_count")
	 */
	private $referralCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Referer\Referral", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="tbl_wonder_howto_referral", inverseJoinColumns={@ORM\JoinColumn(name="referral_id", referencedColumnName="id", unique=true)})
	 */
	protected $referrals;

	/////

	private $isShown = true;

	/////

    public function __construct() {
        $this->articles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->creations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->workshops = new \Doctrine\Common\Collections\ArrayCollection();
        $this->plans = new \Doctrine\Common\Collections\ArrayCollection();
		$this->referrals = new \Doctrine\Common\Collections\ArrayCollection();
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

	// HtmlBody /////

	public function setHtmlBody($htmlBody) {
		$this->htmlBody = $htmlBody;
		return $this;
	}

	public function getHtmlBody() {
		return $this->htmlBody;
	}

	// BodyBlockVideoCount /////

	public function setBodyBlockVideoCount($bodyBlockVideoCount) {
		$this->bodyBlockVideoCount = $bodyBlockVideoCount;
		return $this;
	}

	public function getBodyBlockVideoCount() {
		return $this->bodyBlockVideoCount;
	}

	// BodyExtract /////

	public function getBodyExtract() {
		return $this->getHtmlBody();
	}

	// MainPicture /////

    public function setMainPicture(\Ladb\CoreBundle\Entity\Picture $mainPicture = null) {
        $this->mainPicture = $mainPicture;
        return $this;
    }

    public function getMainPicture() {
        return $this->mainPicture;
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

    public function addArticle(\Ladb\CoreBundle\Entity\Howto\Article $article) {
        if (!$this->articles->contains($article)) {
            $this->articles[] = $article;
            $article->setHowto($this);
        }
        return $this;
    }

    public function removeArticle(\Ladb\CoreBundle\Entity\Howto\Article $article) {
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

	public function addPlan(\Ladb\CoreBundle\Entity\Wonder\Plan $plan) {
		if (!$this->plans->contains($plan)) {
			$this->plans[] = $plan;
			$this->planCount = count($this->plans);
			if (!$this->getIsDraft()) {
				$plan->incrementHowtoCount();
			}
		}
		return $this;
	}

	public function removePlan(\Ladb\CoreBundle\Entity\Wonder\Plan $plan) {
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

	// License /////

	public function setLicense($license) {
		$this->license = $license;
	}

	public function getLicense() {
		if (is_null($this->license)) {
			return new \Ladb\CoreBundle\Entity\License();
		}
		return $this->license;
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

	// Spotlight /////

	public function setSpotlight(\Ladb\CoreBundle\Entity\Spotlight $spotlight = null) {
		$this->spotlight = $spotlight;
		return $this;
	}

	public function getSpotlight() {
		return $this->spotlight;
	}

	// Sticker /////

	public function setSticker(\Ladb\CoreBundle\Entity\Picture $sticker = null) {
		$this->sticker = $sticker;
		return $this;
	}

	public function getSticker() {
		return $this->sticker;
	}

	// Referrals /////

	public function addReferral(\Ladb\CoreBundle\Entity\Referer\Referral $referral) {
		if (!$this->referrals->contains($referral)) {
			$this->referrals[] = $referral;
			$this->referralCount = count($this->referrals);
			$referral->setEntityType($this->getType());
			$referral->setEntityId($this->getId());
		}
		return $this;
	}

	public function removeReferral(\Ladb\CoreBundle\Entity\Referer\Referral $referral) {
		$this->referrals->removeElement($referral);
		$referral->setEntityType(null);
		$referral->setEntityId(null);
	}

	public function getReferrals() {
		return $this->referrals;
	}

	// ReferralCount /////

	public function incrementReferralCount($by = 1) {
		$this->referralCount += intval($by);
	}

	public function getReferralCount() {
		return $this->referralCount;
	}

}