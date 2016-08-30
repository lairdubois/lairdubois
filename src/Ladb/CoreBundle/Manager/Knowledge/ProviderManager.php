<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\Provider;

class ProviderManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.provider_manager';

	public function delete(Provider $provider, $withWitness = true, $flush = true) {
		parent::deleteKnowledge($provider, $withWitness, $flush);
	}

}