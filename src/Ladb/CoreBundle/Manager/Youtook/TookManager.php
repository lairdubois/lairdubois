<?php

namespace Ladb\CoreBundle\Manager\Youtook;

use Ladb\CoreBundle\Entity\Youtook\Took;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;

class TookManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.took_manager';

	/////

	public function delete(Took $took, $withWitness = true, $flush = true) {
		parent::deletePublication($took, $withWitness, $flush);
	}


}