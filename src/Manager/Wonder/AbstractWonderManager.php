<?php

namespace App\Manager\Wonder;

use App\Entity\Wonder\AbstractWonder;
use App\Manager\AbstractAuthoredPublicationManager;
use App\Manager\AbstractPublicationManager;

abstract class AbstractWonderManager extends AbstractAuthoredPublicationManager {

	protected function deleteWonder(AbstractWonder $wonder, $withWitness = true, $flush = true) {
		parent::deletePublication($wonder, $withWitness, $flush);
	}

}