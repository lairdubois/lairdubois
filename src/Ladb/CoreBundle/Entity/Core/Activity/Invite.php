<?php

namespace Ladb\CoreBundle\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_invite")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Activity\InviteRepository")
 */
class Invite extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Core\Activity\Invite';
	const STRIPPED_NAME = 'invite';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\MemberInvitation")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $invitation;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Invitation /////

	public function setInvitation(\Ladb\CoreBundle\Entity\Core\MemberInvitation $invitation) {
		$this->invitation = $invitation;
		return $this;
	}

	public function getInvitation() {
		return $this->invitation;
	}

}