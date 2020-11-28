<?php

namespace Ladb\CoreBundle\Manager\Wonder;

use Ladb\CoreBundle\Entity\Wonder\AbstractWonder;
use Ladb\CoreBundle\Manager\AbstractAuthoredPublicationManager;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;

abstract class AbstractWonderManager extends AbstractAuthoredPublicationManager {

	protected function deleteWonder(AbstractWonder $wonder, $withWitness = true, $flush = true) {
		parent::deletePublication($wonder, $withWitness, $flush);
	}

}