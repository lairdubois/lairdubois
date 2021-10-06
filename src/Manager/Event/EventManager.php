<?php

namespace App\Manager\Event;

use App\Entity\Core\User;
use App\Entity\Event\Event;
use App\Manager\AbstractAuthoredPublicationManager;
use App\Utils\FeedbackableUtils;
use App\Utils\JoinableUtils;

class EventManager extends AbstractAuthoredPublicationManager {

	const NAME = 'ladb_core.event_event_manager';

	/////

	public function publish(Event $event, $flush = true) {

		$event->getUser()->getMeta()->incrementPrivateEventCount(-1);
		$event->getUser()->getMeta()->incrementPublicEventCount();

		parent::publishPublication($event, $flush);
	}

	public function unpublish(Event $event, $flush = true) {

		$event->getUser()->getMeta()->incrementPrivateEventCount(1);
		$event->getUser()->getMeta()->incrementPublicEventCount(-1);

		parent::unpublishPublication($event, $flush);
	}

	public function delete(Event $event, $withWitness = true, $flush = true) {

		// Decrement user event count
		if ($event->getIsDraft()) {
			$event->getUser()->getMeta()->incrementPrivateEventCount(-1);
		} else {
			$event->getUser()->getMeta()->incrementPublicEventCount(-1);
		}

		// Delete joins
		$joinableUtils = $this->get(JoinableUtils::class);
		$joinableUtils->deleteJoins($event, false);

		// Delete feedbacks
		$feedbackableUtils = $this->get(FeedbackableUtils::class);
		$feedbackableUtils->deleteFeedbacks($event, false);

		parent::deletePublication($event, $withWitness, $flush);
	}

	//////

	public function changeOwner(Event $event, User $user, $flush = true) {
		parent::changeOwnerPublication($event, $user, $flush);
	}

	protected function updateUserCounterAfterChangeOwner(User $user, $by, $isPrivate) {
		if ($isPrivate) {
			$user->getMeta()->incrementPrivateEventCount($by);
		} else {
			$user->getMeta()->incrementPublicEventCount($by);
		}
	}

}