<?php

namespace App\Manager\Knowledge;

use App\Entity\Knowledge\Provider;
use App\Utils\ReviewableUtils;

class ProviderManager extends AbstractKnowledgeManager {

	public function delete(Provider $provider, $withWitness = true, $flush = true) {

		// Unlink creations
		foreach ($provider->getCreations() as $creation) {
			$creation->removeProvider($provider);
		}

		// Unlink howtos
		foreach ($provider->getHowtos() as $howto) {
			$howto->removeProvider($provider);
		}

		// Delete reviews
		$reviewableUtils = $this->get(ReviewableUtils::class);
		$reviewableUtils->deleteReviews($provider, false);

		parent::deleteKnowledge($provider, $withWitness, $flush);
	}

}