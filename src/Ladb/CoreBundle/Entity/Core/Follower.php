<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_follower")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\FollowerRepository")
 */
class Follower {

	const CLASS_NAME = 'LadbCoreBundle:Core\Follower';

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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User", inversedBy="followers")
	 * @ORM\JoinColumn(name="following_user_id", nullable=false)
	 */
	private $followingUser;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	// ID /////

	public function getId() {
		return $this->id;
	}

	// FollowingUserId /////

	public function getFollowingUserId() {
		return $this->followingUserId;
	}

	public function setFollowingUserId($followingUserId) {
		$this->followingUserId = $followingUserId;
		return $this;
	}

	// FollowingUser /////

	public function getFollowingUser() {
		return $this->followingUser;
	}

	public function setFollowingUser($followingUser) {
		$this->followingUser = $followingUser;
		if (!is_null($followingUser)) {
			$this->followingUserId = $followingUser->getId();
		}
		return $this;
	}

	// User /////

	public function getUser() {
		return $this->user;
	}

	public function setUser($user) {
		$this->user = $user;
		return $this;
	}

}