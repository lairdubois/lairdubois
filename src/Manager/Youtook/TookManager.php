<?php

namespace App\Manager\Youtook;

use App\Entity\Youtook\Took;
use App\Manager\AbstractPublicationManager;

class TookManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.took_manager';

	/////

	public function delete(Took $took, $withWitness = true, $flush = true) {
		parent::deletePublication($took, $withWitness, $flush);
	}


}