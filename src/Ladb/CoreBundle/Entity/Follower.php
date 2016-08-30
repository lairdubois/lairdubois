<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_follower")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\FollowerRepository")
 */
class Follower {

	const CLASS_NAME = 'LadbCoreBundle:Follower';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(name="following_user_id", type="integer")
	 */
	private $followingUserId;

	/**
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="followers")
	 * @ORM\JoinColumn(name="following_user_id", nullable=false)
	 */
	private $followingUser;

	/**
	 * @ORM\ManyToOne(targetEntity="User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	// ID /////

	public function getId() {
		return $this->id;
	}

	// FollowingUserId /////

	public function setFollowingUserId($followingUserId) {
		$this->followingUserId = $followingUserId;
		return $this;
	}

	public function getFollowingUserId() {
		return $this->followingUserId;
	}

	// FollowingUser /////

	public function setFollowingUser($followingUser) {
		$this->followingUser = $followingUser;
		if (!is_null($followingUser)) {
			$this->followingUserId = $followingUser->getId();
		}
		return $this;
	}

	public function getFollowingUser() {
		return $this->followingUser;
	}

	// User /////

	public function setUser($user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

}