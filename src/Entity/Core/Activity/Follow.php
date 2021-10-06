<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_follow")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\FollowRepository")
 */
class Follow extends AbstractActivity {

	const CLASS_NAME = 'App\Entity\Core\Activity\Follow';
	const STRIPPED_NAME = 'follow';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Follower")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $follower;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Follower /////

	public function setFollower(\App\Entity\Core\Follower $follower) {
		$this->follower = $follower;
		return $this;
	}

	public function getFollower() {
		return $this->follower;
	}

}