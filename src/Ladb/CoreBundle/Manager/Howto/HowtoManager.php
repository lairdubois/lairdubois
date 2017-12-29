<?php

namespace Ladb\CoreBundle\Manager\Howto;

use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;

class HowtoManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.howto_manager';

	/////

	public function publish(Howto $howto, $flush = true) {

		$howto->getUser()->getMeta()->incrementPrivateHowtoCount(-1);
		$howto->getUser()->getMeta()->incrementPublicHowtoCount();

		// Creations counter update
		foreach ($howto->getCreations() as $creation) {
			$creation->incrementHowtoCount(1);
		}

		// Plans counter update
		foreach ($howto->getPlans() as $plan) {
			$plan->incrementHowtoCount(1);
		}

		// Workflows counter update
		foreach ($howto->getWorkflows() as $workflow) {
			$workflow->incrementHowtoCount(1);
		}

		// Workshops counter update
		foreach ($howto->getWorkshops() as $workshop) {
			$workshop->incrementHowtoCount(1);
		}

		// Providers counter update
		foreach ($howto->getProviders() as $provider) {
			$provider->incrementHowtoCount(1);
		}

		// Witness articles
		$witnessManager = $this->get(WitnessManager::NAME);
		foreach ($howto->getArticles() as $article) {
			$witnessManager->deleteByPublication($article, false);
		}

		parent::publishPublication($howto, $flush);
	}

	public function unpublish(Howto $howto, $flush = true) {

		$howto->getUser()->getMeta()->incrementPrivateHowtoCount(1);
		$howto->getUser()->getMeta()->incrementPublicHowtoCount(-1);

		// Creations counter update
		foreach ($howto->getCreations() as $creation) {
			$creation->incrementHowtoCount(-1);
		}

		// Plans counter update
		foreach ($howto->getPlans() as $plan) {
			$plan->incrementHowtoCount(-1);
		}

		// Workflows counter update
		foreach ($howto->getWorkflows() as $workflow) {
			$workflow->incrementHowtoCount(-1);
		}

		// Workshops counter update
		foreach ($howto->getWorkshops() as $workshop) {
			$workshop->incrementHowtoCount(-1);
		}

		// Providers counter update
		foreach ($howto->getProviders() as $provider) {
			$provider->incrementHowtoCount(-1);
		}

		// Witness articles
		$witnessManager = $this->get(WitnessManager::NAME);
		foreach ($howto->getArticles() as $article) {
			if (!$article->getIsDraft()) {
				$witnessManager->createUnpublishedByPublication($article, false);
			}
		}

		// Disable spotlight if howto is spotlighted
		if (!is_null($howto->getSpotlight())) {
			$howto->getSpotlight()->setEnabled(false);
		}

		parent::unpublishPublication($howto, $flush);
	}

	public function delete(Howto $howto, $withWitness = true, $flush = true) {

		// Decrement user howto count
		if ($howto->getIsDraft() === true) {
			$howto->getUser()->getMeta()->incrementPrivateHowtoCount(-1);
		} else {
			$howto->getUser()->getMeta()->incrementPublicHowtoCount(-1);
		}

		// Unlink creations
		foreach ($howto->getCreations() as $creation) {
			$creation->removeHowto($howto);
		}

		// Unlink plans
		foreach ($howto->getPlans() as $plan) {
			$howto->removePlan($plan);
		}

		// Unlink workshops
		foreach ($howto->getWorkshops() as $workshop) {
			$workshop->removeHowto($howto);
		}

		// Unlink providers
		foreach ($howto->getProviders() as $provider) {
			$howto->removeProvider($provider);
		}

		// Witness articles
		$witnessManager = $this->get(WitnessManager::NAME);
		foreach ($howto->getArticles() as $article) {
			$witnessManager->createDeletedByPublication($article, false);
		}

		parent::deletePublication($howto, $withWitness, $flush);
	}

}