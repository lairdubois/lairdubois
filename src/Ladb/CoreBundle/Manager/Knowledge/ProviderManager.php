<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\Provider;
use Ladb\CoreBundle\Utils\ReviewableUtils;

class ProviderManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.provider_manager';

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
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$reviewableUtils->deleteReviews($provider, false);

		parent::deleteKnowledge($provider, $withWitness, $flush);
	}

}