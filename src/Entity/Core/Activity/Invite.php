<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_invite")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\InviteRepository")
 */
class Invite extends AbstractActivity {

	const STRIPPED_NAME = 'invite';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\MemberInvitation")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $invitation;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Invitation /////

	public function setInvitation(\App\Entity\Core\MemberInvitation $invitation) {
		$this->invitation = $invitation;
		return $this;
	}

	public function getInvitation() {
		return $this->invitation;
	}

}