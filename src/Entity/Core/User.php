<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Validator\Constraints as LadbAssert;
use App\Model\LocalisableInterface;
use App\Model\IndexableInterface;
use App\Model\IndexableTrait;
use App\Model\LocalisableTrait;
use App\Model\SitemapableInterface;
use App\Model\SitemapableTrait;

/**
 * @ORM\Table("tbl_core_user")
 * @ORM\Entity(repositoryClass="App\Repository\Core\UserRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity("email")
 * @LadbAssert\ValidUsername()
 * @LadbAssert\ValidDisplayname()
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, IndexableInterface, SitemapableInterface, LocalisableInterface {

	use IndexableTrait, SitemapableTrait, LocalisableTrait;

    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

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
     * @ORM\Column(type="string", length=180)
     * @Assert\Length(min=3, max=25)
     */
    protected $username;

    /**
     * @ORM\Column(name="username_canonical", type="string", length=180, unique=true)
     * @Assert\Length(min=3, max=25)
     */
    protected $usernameCanonical;

    /**
     * @ORM\Column(type="string", length=180)
     * @Assert\NotBlank(groups={"settings"})
     * @Assert\Email(groups={"settings"})
     */
    protected $email;

    /**
     * @ORM\Column(name="email_canonical", type="string", length=180, unique=true)
     */
    protected $emailCanonical;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $salt;

    /**
     * @ORM\Column(type="string")
     */
    protected $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    /**
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\Column(name="confirmation_token", type="string", length=180, unique=true, nullable=true)
     */
    protected $confirmationToken;

    /**
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    protected $passwordRequestedAt;

    /**
     * @ORM\Column(type="array")
     */
    protected $roles;

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
	 * @Assert\Length(min=3, max=25, groups={"settings"})
	 * @Assert\NotBlank(groups={"settings"})
	 * @Assert\Regex("/^[ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'°’’-]+$/")
	 */
	private $displayname;

	/**
	 * @ORM\Column(type="string", length=25, name="displayname_canonical")
	 */
	private $displaynameCanonical;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 * @Assert\Length(min=3, max=100, groups={"settings"})
	 * @Assert\Regex("/^[A-Za-z][ a-zA-Z0-9ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'°’’-]+$/")
	 */
	private $fullname;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Picture", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="avatar_id", nullable=true)
	 * @Assert\Type(type="App\Entity\Core\Picture")
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
	 * @ORM\OneToMany(targetEntity="App\Entity\Core\Follower", mappedBy="followingUser")
	 */
	private $followers;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\UserMeta", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="meta_id", nullable=true)
	 */
	private $meta = null;

	// Team /////

	/**
	 * @ORM\Column(type="boolean", name="is_team", nullable=false)
	 */
	private $isTeam = false;

	/////

	public function __construct() {
                 $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
                 $this->enabled = false;
                 $this->roles = array();
         		$this->skills = new \Doctrine\Common\Collections\ArrayCollection();
         	}

	// ID /////

	public function getId() {
         		return $this->id;
         	}

    /**
     * @return string
     */
    public function __toString() {
        return (string) $this->getUsername();
    }

    /**
     * {@inheritdoc}
     */
    public function addRole($role) {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials() {
        $this->plainPassword = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserIdentifier() {
        return $this->getUsername();
    }

    /**
     * {@inheritdoc}
     */
    public function getUsernameCanonical() {
        return $this->usernameCanonical;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt() {
        return $this->salt;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailCanonical() {
        return $this->emailCanonical;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): ?string {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlainPassword() {
        return $this->plainPassword;
    }

    /**
     * Gets the last login time.
     *
     * @return \DateTime|null
     */
    public function getLastLogin() {
        return $this->lastLogin;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmationToken() {
        return $this->confirmationToken;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles() {
        $roles = $this->roles;

        // we need to make sure to have at least one role
        $roles[] = static::ROLE_DEFAULT;

        return array_unique($roles);
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole($role) {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuperAdmin() {
        return $this->hasRole(static::ROLE_SUPER_ADMIN);
    }

    /**
     * {@inheritdoc}
     */
    public function removeRole($role) {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUsername($username) {
        $this->username = $username;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUsernameCanonical($usernameCanonical) {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSalt($salt) {
        $this->salt = $salt;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmail($email) {
        if ($email != $this->email) {
            $this->setEmailConfirmed(false);
        }
        $this->email = $email;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmailCanonical($emailCanonical) {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($boolean) {
        $this->enabled = (bool) $boolean;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPassword($password) {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSuperAdmin($boolean) {
        if (true === $boolean) {
            $this->addRole(static::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(static::ROLE_SUPER_ADMIN);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPlainPassword($password) {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLastLogin(\DateTime $time = null) {
        $this->lastLogin = $time;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfirmationToken($confirmationToken) {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPasswordRequestedAt(\DateTime $date = null) {
        $this->passwordRequestedAt = $date;

        return $this;
    }

    /**
     * Gets the timestamp that the user requested a password reset.
     *
     * @return null|\DateTime
     */
    public function getPasswordRequestedAt() {
        return $this->passwordRequestedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordRequestNonExpired($ttl) {
        return $this->getPasswordRequestedAt() instanceof \DateTime &&
            $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    /**
     * {@inheritdoc}
     */
    public function setRoles(array $roles) {
        $this->roles = array();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
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

	// DisplaynameCanonical /////

	public function setDisplaynameCanonical($displaynameCanonical) {
		$this->displaynameCanonical = $displaynameCanonical;
		return $this;
	}

	public function getDisplaynameCanonical() {
		return $this->displaynameCanonical;
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

	public function setAvatar(\App\Entity\Core\Picture $avatar = null) {
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
	
	// IsTeam /////

	public function setIsTeam($isTeam) {
		$this->isTeam = $isTeam;
		return $this;
	}

	public function getIsTeam() {
		return $this->isTeam;
	}

}