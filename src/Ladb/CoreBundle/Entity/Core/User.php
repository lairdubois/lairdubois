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
	const ACCOUNT_TYPE_BRAND = 4;

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
	 * @Assert\NotBlank(groups={"settings"})
	 * @Assert\Email(strict=true, groups={"settings"})
	 */
	protected $email;

	/**
	 * @ORM\Column(type="string", length=25, unique=true)
	 * @Assert\Length(min=3, max=25, groups={"settings"})
	 * @Assert\NotBlank(groups={"settings"})
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’°-]+$/")
	 */
	private $displayname;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 * @Assert\Length(min=3, max=100, groups={"settings"})
	 * @Assert\Regex("/^[A-Za-z][ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'’-]+$/")
	 */
	private $fullname;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="avatar_id", nullable=true)
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
	 */
	private $avatar;

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
	 * @ORM\Column(type="smallint", name="account_type")
	 */
	private $accountType = User::ACCOUNT_TYPE_NONE;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Follower", mappedBy="followingUser")
	 */
	private $followers;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\UserMeta", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="meta_id", nullable=true)
	 */
	private $meta = null;

	// Team /////

	/**
	 * @ORM\Column(type="boolean", name="is_team", nullable=false)
	 */
	private $isTeam = false;

	/**
	 * @ORM\Column(type="integer", name="team_count")
	 */
	private $teamCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\User", mappedBy="members")
	 */
	private $teams;

	/**
	 * @ORM\Column(type="integer", name="member_count")
	 */
	private $memberCount = 0;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\User", inversedBy="teams", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_core_user_member",
	 *      	joinColumns={ @ORM\JoinColumn(name="user_id", referencedColumnName="id") },
	 *      	inverseJoinColumns={ @ORM\JoinColumn(name="member_user_id", referencedColumnName="id") }
	 *      )
	 */
	private $members;

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
		$this->members = new \Doctrine\Common\Collections\ArrayCollection();
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
			case User::ACCOUNT_TYPE_BRAND:
				return 'brand';
		}
		return 'none';
	}

	// Meta /////

	public function getMeta() {
		if (is_null($this->meta)) {
			$this->meta = new UserMeta();
		}
		return $this->meta;
	}
	
	// Team /////

	// IsTeam /////

	public function setIsTeam($isTeam) {
		$this->isTeam = $isTeam;
		return $this;
	}

	public function getIsTeam() {
		return $this->isTeam;
	}

	// TeamCount /////

	public function incrementTeamCount($by = 1) {
		return $this->teamCount += intval($by);
	}

	public function getTeamCount() {
		return $this->teamCount;
	}

	// Teams /////

	public function getTeams() {
		return $this->teams;
	}

	// MemberCount /////

	public function getMemberCount() {
		return $this->memberCount;
	}

	// Members /////

	public function addMember(User $member) {
		assert($member !== $this, 'Member can\'t be itself.');
		if (!$this->members->contains($member)) {
			$this->members[] = $member;
			$this->memberCount = count($this->members);
			$member->incrementTeamCount();
		}
		return $this;
	}

	public function removeMember(User $member) {
		if ($this->members->removeElement($member)) {
			$this->memberCount = count($this->members);
			$member->incrementTeamCount(-1);
		}
	}

	public function getMembers() {
		return $this->members;
	}

}