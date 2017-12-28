<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\LocalisableInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\IndexableTrait;
use Ladb\CoreBundle\Model\LocalisableTrait;
use Ladb\CoreBundle\Model\SitemapableInterface;
use Ladb\CoreBundle\Model\SitemapableTrait;

/**
 * @ORM\Table("tbl_core_user")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\UserRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity("email")
 * @UniqueEntity("displayname")
 * @LadbAssert\ValidUsername()
 */
class User extends \FOS\UserBundle\Model\User implements IndexableInterface, SitemapableInterface, LocalisableInterface {

	use IndexableTrait, SitemapableTrait, LocalisableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Core\User';

	const ACCOUNT_TYPE_NONE = 0;
	const ACCOUNT_TYPE_ASSO = 1;
	const ACCOUNT_TYPE_PRO = 2;
	const ACCOUNT_TYPE_HOBBYIST = 3;

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	private $createdAt;

	/**
	 * @ORM\Column(name="updated_at", type="datetime")
	 * @Gedmo\Timestampable(on="update")
	 */
	private $updatedAt;

	/**
	 * @ORM\Column(type="boolean", nullable=true, name="email_confirmed")
	 */
	private $emailConfirmed = false;

	/**
	 * @Assert\Length(min=3, max=25)
	 */
	protected $username;

	/**
	 * @Assert\NotBlank()
	 * @Assert\Email(strict=true)
	 */
	protected $email;

	/**
	 * @ORM\Column(type="string", length=25, unique=true)
	 * @Assert\Length(min=3, max=25)
	 * @Assert\NotBlank(groups={"settings"})
	 * @Assert\Regex("/^[A-Za-z][ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ-]+$/")
	 */
	private $displayname;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 * @Assert\Length(min=3, max=100)
	 * @Assert\Regex("/^[A-Za-z][ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ-]+$/")
	 */
	private $fullname;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="avatar_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
	 */
	private $avatar;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="banner_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
	 */
	private $banner;

	/**
	 * @ORM\Column(type="smallint", name="account_type")
	 */
	private $accountType = User::ACCOUNT_TYPE_NONE;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 * @Assert\Url()
	 */
	private $website;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 * @Assert\Regex("/^[a-zA-Z0-9.]+$/")
	 */
	private $facebook;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 * @Assert\Regex("/^[a-zA-Z0-9_]{1,15}$/")
	 */
	private $twitter;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 * @Assert\Regex("/^[0-9]+|\+[a-zA-Z0-9-]+$/")
	 */
	private $googleplus;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 * @Assert\Regex("/^[a-zA-Z0-9-_]+$/")
	 */
	private $youtube;

	/**
	 * @ORM\Column(type="string", length=24, nullable=true)
	 * @Assert\Regex("/^[a-zA-Z0-9]+$/")
	 */
	private $vimeo;

	/**
	 * @ORM\Column(type="string", length=24, nullable=true)
	 * @Assert\Regex("/^[a-zA-Z0-9]+$/")
	 */
	private $dailymotion;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 * @Assert\Regex("/^[a-zA-Z0-9]+$/")
	 */
	private $pinterest;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 * @Assert\Regex("/^[a-zA-Z0-9_]+$/")
	 */
	private $instagram;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $location;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $latitude;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $longitude;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Biography", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, name="biography_id")
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Biography")
	 */
	private $biography;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Input\Skill", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_core_user_skill")
	 */
	private $skills;

	/**
     * @ORM\Column(type="boolean", nullable=true, name="auto_watch_enabled")
     */
    private $autoWatchEnabled = true;

    /**
	 * @ORM\Column(type="boolean", nullable=true, name="incoming_message_email_notification_enabled")
	 */
	private $incomingMessageEmailNotificationEnabled = true;

	/**
	 * @ORM\Column(type="boolean", nullable=true, name="new_follower_email_notification_enabled")
	 */
	private $newFollowerEmailNotificationEnabled = true;

	/**
	 * @ORM\Column(type="boolean", nullable=true, name="new_like_email_notification_enabled")
	 */
	private $newLikeEmailNotificationEnabled = true;

	/**
	 * @ORM\Column(type="boolean", nullable=true, name="new_vote_email_notification_enabled")
	 */
	private $newVoteEmailNotificationEnabled = true;

	/**
	 * @ORM\Column(type="boolean", nullable=true, name="new_following_post_email_notification_enabled")
	 */
	private $newFollowingPostEmailNotificationEnabled = true;

	/**
	 * @ORM\Column(type="boolean", nullable=true, name="new_watch_activity_email_notification_enabled")
	 */
	private $newWatchActivityEmailNotificationEnabled = true;

	/**
	 * @ORM\Column(type="boolean", nullable=true, name="new_spotlight_email_notification_enabled")
	 */
	private $newSpotlightEmailNotificationEnabled = true;

	/**
	 * @ORM\Column(type="boolean", nullable=true, name="week_news_email_enabled")
	 */
	private $weekNewsEmailEnabled = true;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Follower", mappedBy="followingUser")
	 */
	private $followers;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="follower_count")
	 */
	private $followerCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="following_count")
	 */
	private $followingCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="recieved_like_count")
	 */
	private $recievedLikeCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="sent_like_count")
	 */
	private $sentLikeCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="positive_vote_count")
	 */
	private $positiveVoteCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="negative_vote_count")
	 */
	private $negativeVoteCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="unread_message_count")
	 */
	private $unreadMessageCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="fresh_notification_count")
	 */
	private $freshNotificationCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="contribution_count")
	 */
	private $contributionCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="comment_count")
	 */
	private $commentCount = 0;


	/**
	 * @ORM\Column(type="integer", nullable=true, name="draft_creation_count")
	 */
	private $draftCreationCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="published_creation_count")
	 */
	private $publishedCreationCount = 0;


	/**
     * @ORM\Column(type="integer", nullable=true, name="draft_plan_count")
     */
	private $draftPlanCount = 0;

    /**
     * @ORM\Column(type="integer", nullable=true, name="published_plan_count")
     */
	private $publishedPlanCount = 0;


	/**
	 * @ORM\Column(type="integer", nullable=true, name="draft_howto_count")
	 */
	private $draftHowtoCount = 0;

    /**
	 * @ORM\Column(type="integer", nullable=true, name="published_howto_count")
	 */
	private $publishedHowtoCount = 0;


	/**
	 * @ORM\Column(type="integer", nullable=true, name="draft_workshop_count")
	 */
	private $draftWorkshopCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="published_workshop_count")
	 */
	private $publishedWorkshopCount = 0;


	/**
	 * @ORM\Column(type="integer", nullable=true, name="draft_find_count")
	 */
	private $draftFindCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="published_find_count")
	 */
	private $publishedFindCount = 0;


	/**
	 * @ORM\Column(type="integer", nullable=true, name="draft_question_count")
	 */
	private $draftQuestionCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="published_question_count")
	 */
	private $publishedQuestionCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="answer_count")
	 */
	private $answerCount = 0;


	/**
	 * @ORM\Column(type="integer", nullable=true, name="draft_graphic_count")
	 */
	private $draftGraphicCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="published_graphic_count")
	 */
	private $publishedGraphicCount = 0;


	/**
	 * @ORM\Column(type="integer", nullable=true, name="proposal_count")
	 */
	private $proposalCount = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true, name="testimonial_count")
	 */
	private $testimonialCount = 0;


	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\UserMeta", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="meta_id", nullable=true)
	 */
	private $meta = null;

	/////

	/**
	 * @ORM\PrePersist()
	 */
	public function prePersist() {
		if (is_null($this->displayname)) {
			$this->displayname = $this->username;
		}
	}

	/////

	public function __construct() {
		parent::__construct();
		$this->skills = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// ID /////

	public function getId() {
		return $this->id;
	}

	public function setEmail($email) {
		if ($email != $this->email) {
			$this->setEmailConfirmed(false);
		}
		return parent::setEmail($email);
	}

	// IsIndexable /////

	public function isIndexable() {
		return $this->isEnabled();
	}

	// EmailConfirmed /////

	public function setEmailConfirmed($emailConfirmed) {
		$this->emailConfirmed = $emailConfirmed;
		return $this;
	}

	public function getEmailConfirmed() {
		return $this->emailConfirmed;
	}

	// Slug /////

	public function setSlug($slug) {
		$this->slug = $slug;
		return $this;
	}

	public function getSlug() {
		return $this->slug;
	}

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// UpdatedAt /////

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	// Displayname /////

	public function setDisplayname($displayname) {
		$this->displayname = $displayname;
		return $this;
	}

	public function getDisplayname() {
		return $this->displayname;
	}

	public function getTitle() {
		return $this->getDisplayname();
	}

	// Fullname /////

	public function setFullname($fullname) {
		$this->fullname = $fullname;
		return $this;
	}

	public function getFullname() {
		return $this->fullname;
	}

	public function isFullnameDisplayble() {
		return !is_null($this->fullname) && strlen($this->fullname) > 0 && $this->fullname != $this->displayname;
	}

	// Avatar /////

	public function setAvatar(\Ladb\CoreBundle\Entity\Core\Picture $avatar = null) {
		$this->avatar = $avatar;
		return $this;
	}

	public function getAvatar() {
		return $this->avatar;
	}

	public function getMainPicture() {
		return $this->getAvatar();
	}

	// AccountType /////

	public function setAccountType($accountType) {
		$this->accountType = $accountType;
		return $this;
	}

	public function getAccountType() {
		return $this->accountType;
	}

	// MarkerIcon /////

	public function getMarkerIcon() {
		switch ($this->accountType) {
			case User::ACCOUNT_TYPE_ASSO:
				return 'asso';
			case User::ACCOUNT_TYPE_PRO:
				return 'pro';
			case User::ACCOUNT_TYPE_HOBBYIST:
				return 'hobbyist';
		}
		return 'none';
	}

	// DEPRECATED /////

	// Banner /////

	public function getDeprecatedBanner() {
		return $this->banner;
	}

	// Website /////

	public function getDeprecatedWebsite() {
		return $this->website;
	}

	// Facebook /////

	public function getDeprecatedFacebook() {
		return $this->facebook;
	}

	// Twitter /////

	public function getDeprecatedTwitter() {
		return $this->twitter;
	}

	// GooglePlus /////

	public function getDeprecatedGoogleplus() {
		return $this->googleplus;
	}

	// YouTube /////

	public function getDeprecatedYoutube() {
		return $this->youtube;
	}

	// Vimeo /////

	public function getDeprecatedVimeo() {
		return $this->vimeo;
	}

	// Dailymotion /////

	public function getDeprecatedDailymotion() {
		return $this->dailymotion;
	}

	// Pinterest /////

	public function getDeprecatedPinterest() {
		return $this->pinterest;
	}

	// Instagram /////

	public function getDeprecatedInstagram() {
		return $this->instagram;
	}

	// Skills /////

	public function getDeprecatedSkills() {
		return $this->skills;
	}

	// Biography /////

	public function getDeprecatedBiography() {
		return $this->biography;
	}

	// AutoWatchEnabled /////

    public function getDeprecatedAutoWatchEnabled() {
        return $this->autoWatchEnabled;
    }

	// IncomingMessageEmailNotificationEnabled /////

	public function getDeprecatedIncomingMessageEmailNotificationEnabled() {
		return $this->incomingMessageEmailNotificationEnabled;
	}

	// NewFollowerEmailNotificationEnabled /////

	public function getDeprecatedNewFollowerEmailNotificationEnabled() {
		return $this->newFollowerEmailNotificationEnabled;
	}

	// NewLikeEmailNotificationEnabled /////

	public function getDeprecatedNewLikeEmailNotificationEnabled() {
		return $this->newLikeEmailNotificationEnabled;
	}

	// NewVoteEmailNotificationEnabled /////

	public function getDeprecatedNewVoteEmailNotificationEnabled() {
		return $this->newVoteEmailNotificationEnabled;
	}

	// NewFollowingPostEmailNotificationEnabled /////

	public function getDeprecatedNewFollowingPostEmailNotificationEnabled() {
		return $this->newFollowingPostEmailNotificationEnabled;
	}

	// NewWatchActivityEmailNotificationEnabled /////

	public function getDeprecatedNewWatchActivityEmailNotificationEnabled() {
		return $this->newWatchActivityEmailNotificationEnabled;
	}

	// NewSpotlightEmailNotificationEnabled /////

	public function getDeprecatedNewSpotlightEmailNotificationEnabled() {
		return $this->newSpotlightEmailNotificationEnabled;
	}

	// WeekNewsEmailNotificationEnabled /////

	public function getDeprecatedWeekNewsEmailEnabled() {
		return $this->weekNewsEmailEnabled;
	}

	// FollowerCount /////

	public function getDeprecatedFollowerCount() {
		return $this->followerCount;
	}

	// FollowingCount /////

	public function getDeprecatedFollowingCount() {
		return $this->followingCount;
	}

	// RecievedLikeCount /////

	public function getDeprecatedRecievedLikeCount() {
		return $this->recievedLikeCount;
	}

	// SentLikeCount /////

	public function getDeprecatedSentLikeCount() {
		return $this->sentLikeCount;
	}

	// PositiveVoteCount /////

	public function getDeprecatedPositiveVoteCount() {
		return $this->positiveVoteCount;
	}

	// NegativeVoteCount /////

	public function getDeprecatedNegativeVoteCount() {
		return $this->negativeVoteCount;
	}

	// UnreadMessageCount /////

	public function getDeprecatedUnreadMessageCount() {
		return $this->unreadMessageCount;
	}

	// FreshNotificationCount /////

	public function getDeprecatedFreshNotificationCount() {
		return $this->freshNotificationCount;
	}

	// ContributionCount /////

	public function getDeprecatedContributionCount() {
		return $this->contributionCount;
	}

	// CommentCount /////

	public function getDeprecatedCommentCount() {
		return $this->commentCount;
	}

	// DraftCreationCount /////

	public function getDeprecatedDraftCreationCount() {
		return $this->draftCreationCount;
	}

	// PublishedCreationCount /////

	public function getDeprecatedPublishedCreationCount() {
		return $this->publishedCreationCount;
	}

    // DraftPlanCount /////

    public function getDeprecatedDraftPlanCount() {
        return $this->draftPlanCount;
    }

    // PublishedPlanCount /////

    public function getDeprecatedPublishedPlanCount() {
        return $this->publishedPlanCount;
    }

    // DraftHowtoCount /////

	public function getDeprecatedDraftHowtoCount() {
		return $this->draftHowtoCount;
	}

    // PublishedHowtoCount /////

	public function getDeprecatedPublishedHowtoCount() {
		return $this->publishedHowtoCount;
	}

	// DraftWorkshopCount /////

	public function getDeprecatedDraftWorkshopCount() {
		return $this->draftWorkshopCount;
	}

	// PublishedWorkshopCount /////

	public function getDeprecatedPublishedWorkshopCount() {
		return $this->publishedWorkshopCount;
	}

	// DraftFindCount /////

	public function getDeprecatedDraftFindCount() {
		return $this->draftFindCount;
	}

	// PublishedFindCount /////

	public function getDeprecatedPublishedFindCount() {
		return $this->publishedFindCount;
	}

	// DraftQuestionCount /////

	public function getDeprecatedDraftQuestionCount() {
		return $this->draftQuestionCount;
	}

	// PublishedQuestionCount /////

	public function getDeprecatedPublishedQuestionCount() {
		return $this->publishedQuestionCount;
	}

	// AnswerCount /////

	public function getDeprecatedAnswerCount() {
		return $this->answerCount;
	}

	// DraftGraphicCount /////

	public function getDeprecatedDraftGraphicCount() {
		return $this->draftGraphicCount;
	}

	// PublishedGraphicCount /////

	public function getDeprecatedPublishedGraphicCount() {
		return $this->publishedGraphicCount;
	}

	// ProposalCount /////

	public function getDeprecatedProposalCount() {
		return $this->proposalCount;
	}

	// TestimonialCount /////

	public function getDeprecatedTestimonialCount() {
		return $this->testimonialCount;
	}

	// Meta /////

	public function getMeta() {
		if (is_null($this->meta)) {
			$this->meta = new UserMeta();
		}
		return $this->meta;
	}

}