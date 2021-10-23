<?php

namespace App\Manager\Wonder;

use App\Entity\Core\User;
use App\Entity\Wonder\Plan;

class PlanManager extends AbstractWonderManager {

	public function publish(Plan $plan, $flush = true) {

		$plan->getUser()->getMeta()->incrementPrivatePlanCount(-1);
		$plan->getUser()->getMeta()->incrementPublicPlanCount();

		// Questions counter update
		foreach ($plan->getQuestions() as $question) {
			$question->incrementPlanCount(1);
		}

		// Creations counter update
		foreach ($plan->getCreations() as $creation) {
			$creation->incrementPlanCount(1);
		}

		// Workshops counter update
		foreach ($plan->getWorkshops() as $workshop) {
			$workshop->incrementPlanCount(1);
		}

		// Howtos counter update
		foreach ($plan->getHowtos() as $howto) {
			$howto->incrementPlanCount(1);
		}

		// School counter update
		foreach ($plan->getSchools() as $school) {
			$school->incrementPlanCount(1);
		}

		// Inspirations counter update
		foreach ($plan->getInspirations() as $inspiration) {
			$inspiration->incrementReboundCount(1);
		}

		parent::publishPublication($plan, $flush);
	}

	public function unpublish(Plan $plan, $flush = true) {

		$plan->getUser()->getMeta()->incrementPrivatePlanCount(1);
		$plan->getUser()->getMeta()->incrementPublicPlanCount(-1);

		// Questions counter update
		foreach ($plan->getQuestions() as $question) {
			$question->incrementPlanCount(-1);
		}

		// Creations counter update
		foreach ($plan->getCreations() as $creation) {
			$creation->incrementPlanCount(-1);
		}

		// Workshops counter update
		foreach ($plan->getWorkshops() as $workshop) {
			$workshop->incrementPlanCount(-1);
		}

		// Howtos counter update
		foreach ($plan->getHowtos() as $howto) {
			$howto->incrementPlanCount(-1);
		}

		// School counter update
		foreach ($plan->getSchools() as $school) {
			$school->incrementPlanCount(-1);
		}

		// Inspirations counter update
		foreach ($plan->getInspirations() as $inspiration) {
			$inspiration->incrementReboundCount(-1);
		}

		parent::unpublishPublication($plan, $flush);
	}

	public function delete(Plan $plan, $withWitness = true, $flush = true) {

		// Decrement user plan count
		if ($plan->getIsDraft()) {
			$plan->getUser()->getMeta()->incrementPrivatePlanCount(-1);
		} else {
			$plan->getUser()->getMeta()->incrementPublicPlanCount(-1);
		}

		// Unlink creations
		foreach ($plan->getCreations() as $creation) {
			$creation->removePlan($plan);
		}

		// Unlink workshops
		foreach ($plan->getWorkshops() as $workshop) {
			$workshop->removePlan($plan);
		}

		// Unlink howtos
		foreach ($plan->getHowtos() as $howto) {
			$howto->removePlan($plan);
		}

		// Unlink inspirations
		foreach ($plan->getInspirations() as $inspiration) {
			$plan->removeInspiration($inspiration);
		}

		parent::deleteWonder($plan, $withWitness, $flush);
	}

	//////

	public function changeOwner(Plan $plan, User $user, $flush = true) {
		parent::changeOwnerPublication($plan, $user, $flush);
	}

	protected function updateUserCounterAfterChangeOwner(User $user, $by, $isPrivate) {
		if ($isPrivate) {
			$user->getMeta()->incrementPrivatePlanCount($by);
		} else {
			$user->getMeta()->incrementPublicPlanCount($by);
		}
	}

}