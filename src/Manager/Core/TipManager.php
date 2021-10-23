<?php

namespace App\Manager\Core;

use App\Entity\Core\Tip;
use App\Manager\AbstractPublicationManager;

class TipManager extends AbstractPublicationManager {

	public function delete(Tip $tip, $withWitness = true, $flush = true) {
		parent::deletePublication($tip, $withWitness, $flush);
	}

}