<?php

namespace App\Manager\Core;

use App\Entity\Core\MemberInvitation;
use App\Manager\AbstractManager;
use App\Utils\ActivityUtils;

class MemberInvitationManager extends AbstractManager {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.ActivityUtils::class,
        ));
    }

    /////

	public function create(\App\Entity\Core\User $team, \App\Entity\Core\User $sender, \App\Entity\Core\User $recipient, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create the invitation
		$invitation = new MemberInvitation();
		$invitation->setTeam($team);
		$invitation->setSender($sender);
		$invitation->setRecipient($recipient);

		$om->persist($invitation);

		// Update counters

		$team->getMeta()->incrementInvitationCount();
		$recipient->getMeta()->incrementInvitationCount();

		// Create activity
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->createInviteActivity($invitation, false);

		if ($flush) {
			$om->flush();
		}

		return $invitation;
	}

	public function delete(MemberInvitation $invitation, $flush = true) {

		// Delete invitation activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->deleteActivitiesByInvitation($invitation);

		// Update counters

		$invitation->getTeam()->getMeta()->incrementInvitationCount(-1);
		$invitation->getRecipient()->getMeta()->incrementInvitationCount(-1);

		parent::deleteEntity($invitation, $flush);
	}

}