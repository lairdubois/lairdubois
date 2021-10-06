<?php

namespace App\Manager\Core;

use App\Entity\Core\Tip;
use App\Manager\AbstractPublicationManager;

class TipManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.core_tip_manager';

	/////

	public function delete(Tip $tip, $withWitness = true, $flush = true) {
		parent::deletePublication($tip, $withWitness, $flush);
	}

}