<?php

namespace App\Manager\Knowledge;

use App\Entity\Knowledge\Wood;

class WoodManager extends AbstractKnowledgeManager {

	public function delete(Wood $wood, $withWitness = true, $flush = true) {
		parent::deleteKnowledge($wood, $withWitness, $flush);
	}

}