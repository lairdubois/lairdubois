<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\Wood;

class WoodManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.wood_manager';

	public function delete(Wood $wood, $withWitness = true, $flush = true) {
		parent::deleteKnowledge($wood, $withWitness, $flush);
	}

}