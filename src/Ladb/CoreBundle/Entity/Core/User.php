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
	 * @Assert\Length(min=3, max=25)
	 */
	protected $username;
	/**
	 * @Assert\NotBlank()
	 * @Assert\Email(strict=true)
	 */
	protected $email;
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
	 * @ORM\Column(type="integer", nullable=true, name="proposal_count")
	 */
	private $proposalCount = 0;

	/**
	 * @ORM\OneToOne(targetEntity="Ladb\CoreBundle\Entity\Core\UserMeta", mappedBy="user", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, name="meta_id")
	 */
	private $meta = null;

	/////

	public function __construct() {
		parent::__construct();
		$this->skills = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/////

	/**
	 * @ORM\PrePersist()
	 */
	public function prePersist() {
		if (is_null($this->displayname)) {
			$this->displayname = $this->username;
		}
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

	public function getEmailConfirmed() {
		return $this->emailConfirmed;
	}

	public function setEmailConfirmed($emailConfirmed) {
		$this->emailConfirmed = $emailConfirmed;
		return $this;
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

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// UpdatedAt /////

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	// Displayname /////

	public function getTitle() {
		return $this->getDisplayname();
	}

	public function getDisplayname() {
		return $this->displayname;
	}

	public function setDisplayname($displayname) {
		$this->displayname = $displayname;
		return $this;
	}

	// Fullname /////

	public function getFullname() {
		return $this->fullname;
	}

	public function setFullname($fullname) {
		$this->fullname = $fullname;
		return $this;
	}

	public function isFullnameDisplayble() {
		return !is_null($this->fullname) && strlen($this->fullname) > 0 && $this->fullname != $this->displayname;
	}

	// Avatar /////

	public function getMainPicture() {
		return $this->getAvatar();
	}

	public function getAvatar() {
		return $this->avatar;
	}

	public function setAvatar(\Ladb\CoreBundle\Entity\Core\Picture $avatar = null) {
		$this->avatar = $avatar;
		return $this;
	}

	// AccountType /////

	public function getAccountType() {
		return $this->accountType;
	}

	public function setAccountType($accountType) {
		$this->accountType = $accountType;
		return $this;
	}

	// Banner /////

	public function getBanner() {
		return $this->banner;
	}

	public function setBanner(\Ladb\CoreBundle\Entity\Core\Picture $banner = null) {
		$this->banner = $banner;
		return $this;
	}

	// Website /////

	public function getWebsite() {
		return $this->website;
	}

	public function setWebsite($website) {
		$this->website = $website;
	}

	// Facebook /////

	public function getFacebook() {
		return $this->facebook;
	}

	public function setFacebook($facebook) {
		$this->facebook = $facebook;
	}

	// Twitter /////

	public function getTwitter() {
		return $this->twitter;
	}

	public function setTwitter($twitter) {
		$this->twitter = $twitter;
	}

	// GooglePlus /////

	public function getGoogleplus() {
		return $this->googleplus;
	}

	public function setGoogleplus($googleplus) {
		$this->googleplus = $googleplus;
	}

	// YouTube /////

	public function getYoutube() {
		return $this->youtube;
	}

	public function setYoutube($youtube) {
		$this->youtube = $youtube;
	}

	// Vimeo /////

	public function getVimeo() {
		return $this->vimeo;
	}

	public function setVimeo($vimeo) {
		$this->vimeo = $vimeo;
	}

	// Dailymotion /////

	public function getDailymotion() {
		return $this->dailymotion;
	}

	public function setDailymotion($dailymotion) {
		$this->dailymotion = $dailymotion;
	}

	// Pinterest /////

	public function getPinterest() {
		return $this->pinterest;
	}

	public function setPinterest($pinterest) {
		$this->pinterest = $pinterest;
	}

	// Instagram /////

	public function getInstagram() {
		return $this->instagram;
	}

	public function setInstagram($instagram) {
		$this->instagram = $instagram;
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

	// Skills /////

	public function addSkill(\Ladb\CoreBundle\Entity\Input\Skill $skill) {
		$this->skills[] = $skill;
		return $this;
	}

	public function removeSkill(\Ladb\CoreBundle\Entity\Input\Skill $skill) {
		$this->skills->removeElement($skill);
	}

	public function getSkills() {
		return $this->skills;
	}

	// Biography /////

	public function getBiography() {
		return $this->biography;
	}

	public function setBiography(\Ladb\CoreBundle\Entity\Core\Biography $biography = null) {
		$this->biography = $biography;
	}

	// AutoWatchEnabled /////

    public function getAutoWatchEnabled() {
        return $this->autoWatchEnabled;
    }

    public function setAutoWatchEnabled($autoWatchEnabled) {
        $this->autoWatchEnabled = $autoWatchEnabled;
		return $this;
    }

	// IncomingMessageEmailNotificationEnabled /////

	public function getIncomingMessageEmailNotificationEnabled() {
		return $this->incomingMessageEmailNotificationEnabled;
	}

	public function setIncomingMessageEmailNotificationEnabled($incomingMessageEmailNotificationEnabled) {
		$this->incomingMessageEmailNotificationEnabled = $incomingMessageEmailNotificationEnabled;
		return $this;
	}

	// NewFollowerEmailNotificationEnabled /////

	public function getNewFollowerEmailNotificationEnabled() {
		return $this->newFollowerEmailNotificationEnabled;
	}

	public function setNewFollowerEmailNotificationEnabled($newFollowerEmailNotificationEnabled) {
		$this->newFollowerEmailNotificationEnabled = $newFollowerEmailNotificationEnabled;
		return $this;
	}

	// NewLikeEmailNotificationEnabled /////

	public function getNewLikeEmailNotificationEnabled() {
		return $this->newLikeEmailNotificationEnabled;
	}

	public function setNewLikeEmailNotificationEnabled($newLikeEmailNotificationEnabled) {
		$this->newLikeEmailNotificationEnabled = $newLikeEmailNotificationEnabled;
		return $this;
	}

	// NewVoteEmailNotificationEnabled /////

	public function getNewVoteEmailNotificationEnabled() {
		return $this->newVoteEmailNotificationEnabled;
	}

	public function setNewVoteEmailNotificationEnabled($newVoteEmailNotificationEnabled) {
		$this->newVoteEmailNotificationEnabled = $newVoteEmailNotificationEnabled;
		return $this;
	}

	// NewFollowingPostEmailNotificationEnabled /////

	public function getNewFollowingPostEmailNotificationEnabled() {
		return $this->newFollowingPostEmailNotificationEnabled;
	}

	public function setNewFollowingPostEmailNotificationEnabled($newPostEmailNotificationEnabled) {
		$this->newFollowingPostEmailNotificationEnabled = $newPostEmailNotificationEnabled;
		return $this;
	}

	// NewWatchActivityEmailNotificationEnabled /////

	public function getNewWatchActivityEmailNotificationEnabled() {
		return $this->newWatchActivityEmailNotificationEnabled;
	}

	public function setNewWatchActivityEmailNotificationEnabled($newCommentEmailNotificationEnabled) {
		$this->newWatchActivityEmailNotificationEnabled = $newCommentEmailNotificationEnabled;
		return $this;
	}

	// NewSpotlightEmailNotificationEnabled /////

	public function getNewSpotlightEmailNotificationEnabled() {
		return $this->newSpotlightEmailNotificationEnabled;
	}

	public function setNewSpotlightEmailNotificationEnabled($newSpotlightEmailNotificationEnabled) {
		$this->newSpotlightEmailNotificationEnabled = $newSpotlightEmailNotificationEnabled;
		return $this;
	}

	// WeekNewsEmailNotificationEnabled /////

	public function getWeekNewsEmailEnabled() {
		return $this->weekNewsEmailEnabled;
	}

	public function setWeekNewsEmailEnabled($weekNewsEmailEnabled) {
		$this->weekNewsEmailEnabled = $weekNewsEmailEnabled;
		return $this;
	}

	// FollowerCount /////

	public function incrementFollowerCount($by = 1) {
		return $this->followerCount += intval($by);
	}

	public function getFollowerCount() {
		return $this->followerCount;
	}

	// FollowingCount /////

	public function incrementFollowingCount($by = 1) {
		return $this->followingCount += intval($by);
	}

	public function getFollowingCount() {
		return $this->followingCount;
	}

	// RecievedLikeCount /////

	public function incrementRecievedLikeCount($by = 1) {
		return $this->recievedLikeCount += intval($by);
	}

	public function getRecievedLikeCount() {
		return $this->recievedLikeCount;
	}

	// SentLikeCount /////

	public function incrementSentLikeCount($by = 1) {
		return $this->sentLikeCount += intval($by);
	}

	public function getSentLikeCount() {
		return $this->sentLikeCount;
	}

	// PositiveVoteCount /////

	public function incrementPositiveVoteCount($by = 1) {
		return $this->positiveVoteCount += intval($by);
	}

	public function getPositiveVoteCount() {
		return $this->positiveVoteCount;
	}

	// NegativeVoteCount /////

	public function incrementNegativeVoteCount($by = 1) {
		return $this->negativeVoteCount += intval($by);
	}

	public function getNegativeVoteCount() {
		return $this->negativeVoteCount;
	}

	// UnreadMessageCount /////

	public function incrementUnreadMessageCount($by = 1) {
		return $this->unreadMessageCount += intval($by);
	}

	public function getUnreadMessageCount() {
		return $this->unreadMessageCount;
	}

	// FreshNotificationCount /////

	public function incrementFreshNotificationCount($by = 1) {
		return $this->freshNotificationCount += intval($by);
	}

	public function getFreshNotificationCount() {
		return $this->freshNotificationCount;
	}

	public function setFreshNotificationCount($freshNotificationCount) {
		$this->freshNotificationCount = $freshNotificationCount;
		return $this;
	}

	// ContributionCount /////

	public function getContributionCount() {
		return $this->contributionCount;
	}

	public function incrementCommentCount($by = 1) {
		$this->incrementContributionCount($by);
		return $this->commentCount += intval($by);
	}

	// CommentCount /////

	public function incrementContributionCount($by = 1) {
		return $this->contributionCount += intval($by);
	}

	public function getCommentCount() {
		return $this->commentCount;
	}

	// DraftCreationCount /////

	public function incrementDraftCreationCount($by = 1) {
		return $this->draftCreationCount += intval($by);
	}

	public function getDraftCreationCount() {
		return $this->draftCreationCount;
	}

	// PublishedCreationCount /////

	public function incrementPublishedCreationCount($by = 1) {
		$this->incrementContributionCount($by);
		return $this->publishedCreationCount += intval($by);
	}

	public function getPublishedCreationCount() {
		return $this->publishedCreationCount;
	}

    // DraftPlanCount /////

    public function incrementDraftPlanCount($by = 1) {
        return $this->draftPlanCount += intval($by);
    }

    public function getDraftPlanCount() {
        return $this->draftPlanCount;
    }

    // PublishedPlanCount /////

    public function incrementPublishedPlanCount($by = 1) {
        $this->incrementContributionCount($by);
        return $this->publishedPlanCount += intval($by);
    }

    public function getPublishedPlanCount() {
        return $this->publishedPlanCount;
    }

    // DraftHowtoCount /////

	public function incrementDraftHowtoCount($by = 1) {
		return $this->draftHowtoCount += intval($by);
	}

	public function getDraftHowtoCount() {
		return $this->draftHowtoCount;
	}

    // PublishedHowtoCount /////

	public function incrementPublishedHowtoCount($by = 1) {
		$this->incrementContributionCount($by);
		return $this->publishedHowtoCount += intval($by);
	}

	public function getPublishedHowtoCount() {
		return $this->publishedHowtoCount;
	}

	// DraftWorkshopCount /////

	public function incrementDraftWorkshopCount($by = 1) {
		return $this->draftWorkshopCount += intval($by);
	}

	public function getDraftWorkshopCount() {
		return $this->draftWorkshopCount;
	}

	// PublishedWorkshopCount /////

	public function incrementPublishedWorkshopCount($by = 1) {
		$this->incrementContributionCount($by);
		return $this->publishedWorkshopCount += intval($by);
	}

	public function getPublishedWorkshopCount() {
		return $this->publishedWorkshopCount;
	}

	// DraftFindCount /////

	public function incrementDraftFindCount($by = 1) {
		return $this->draftFindCount += intval($by);
	}

	public function getDraftFindCount() {
		return $this->draftFindCount;
	}

	// PublishedFindCount /////

	public function incrementPublishedFindCount($by = 1) {
		$this->incrementContributionCount($by);
		return $this->publishedFindCount += intval($by);
	}

	public function getPublishedFindCount() {
		return $this->publishedFindCount;
	}

	// DraftQuestionCount /////

	public function incrementDraftQuestionCount($by = 1) {
		return $this->draftQuestionCount += intval($by);
	}

	public function getDraftQuestionCount() {
		return $this->draftQuestionCount;
	}

	// PublishedQuestionCount /////

	public function incrementPublishedQuestionCount($by = 1) {
		$this->incrementContributionCount($by);
		return $this->publishedQuestionCount += intval($by);
	}

	public function getPublishedQuestionCount() {
		return $this->publishedQuestionCount;
	}

	// AnswerCount /////

	public function incrementAnswerCount($by = 1) {
		$this->incrementContributionCount($by);
		return $this->answerCount += intval($by);
	}

	public function getAnswerCount() {
		return $this->answerCount;
	}

	// ProposalCount /////

	public function incrementProposalCount($by = 1) {
		$this->incrementContributionCount($by);
		return $this->proposalCount += intval($by);
	}

	public function getProposalCount() {
		return $this->proposalCount;
	}

	// Meta /////

	public function getMeta() {
		if ($this->meta === null) {
			$this->meta = new UserMeta();
			$this->meta->setUser($this);
		}
		return $this->meta;
	}

}