<?php

namespace Ladb\CoreBundle\Manager\Core;

use Ladb\CoreBundle\Entity\Core\Tip;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;

class TipManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.core_tip_manager';

	/////

	public function delete(Tip $tip, $withWitness = true, $flush = true) {
		parent::deletePublication($tip, $withWitness, $flush);
	}

}