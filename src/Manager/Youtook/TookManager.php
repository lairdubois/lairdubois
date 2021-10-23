<?php

namespace App\Manager\Youtook;

use App\Entity\Youtook\Took;
use App\Manager\AbstractPublicationManager;

class TookManager extends AbstractPublicationManager {

	public function delete(Took $took, $withWitness = true, $flush = true) {
		parent::deletePublication($took, $withWitness, $flush);
	}

}