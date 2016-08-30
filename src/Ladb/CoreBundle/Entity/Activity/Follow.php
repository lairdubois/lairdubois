<?php

namespace Ladb\CoreBundle\Entity\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_follow")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Activity\FollowRepository")
 */
class Follow extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Activity\Follow';
	const STRIPPED_NAME = 'follow';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Follower")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $follower;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Follower /////

	public function setFollower(\Ladb\CoreBundle\Entity\Follower $follower) {
		$this->follower = $follower;
		return $this;
	}

	public function getFollower() {
		return $this->follower;
	}

}