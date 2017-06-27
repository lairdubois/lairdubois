<?php

namespace Ladb\CoreBundle\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_follow")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Activity\FollowRepository")
 */
class Follow extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Core\Activity\Follow';
	const STRIPPED_NAME = 'follow';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Follower")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $follower;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Follower /////

	public function getFollower() {
		return $this->follower;
	}

	public function setFollower(\Ladb\CoreBundle\Entity\Core\Follower $follower) {
		$this->follower = $follower;
		return $this;
	}

}