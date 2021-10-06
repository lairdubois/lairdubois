<?php

namespace App\Manager\Core;

use App\Entity\Core\MemberRequest;
use App\Manager\AbstractManager;
use App\Utils\ActivityUtils;

class MemberRequestManager extends AbstractManager {

	const NAME = 'ladb_core.core_member_request_manager';

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), array(
            ActivityUtils::class => '?'.ActivityUtils::class,
        ));
    }

	public function create(\App\Entity\Core\User $team, \App\Entity\Core\User $sender, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create the request
		$request = new MemberRequest();
		$request->setTeam($team);
		$request->setSender($sender);

		$om->persist($request);

		// Update counters

		$team->getMeta()->incrementRequestCount();
		$sender->getMeta()->incrementRequestCount();

		// Create activity
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->createRequestActivity($request, false);

		if ($flush) {
			$om->flush();
		}

		return $request;
	}

	public function delete(MemberRequest $request, $flush = true) {

		// Delete request activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->deleteActivitiesByRequest($request);

		// Update counters

		$request->getTeam()->getMeta()->incrementRequestCount(-1);
		$request->getSender()->getMeta()->incrementRequestCount(-1);

		parent::deleteEntity($request, $flush);
	}

}