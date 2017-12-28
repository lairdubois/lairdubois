<?php

namespace Ladb\CoreBundle\Manager\Promotion;

use Ladb\CoreBundle\Entity\Promotion\Graphic;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;

class GraphicManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.graphic_manager';

	/////

	public function publish(Graphic $graphic, $flush = true) {

		$graphic->getUser()->getMeta()->incrementPrivateGraphicCount(-1);
		$graphic->getUser()->getMeta()->incrementPublicGraphicCount();

		parent::publishPublication($graphic, $flush);
	}

	public function unpublish(Graphic $graphic, $flush = true) {

		$graphic->getUser()->getMeta()->incrementPrivateGraphicCount(1);
		$graphic->getUser()->getMeta()->incrementPublicGraphicCount(-1);

		parent::unpublishPublication($graphic, $flush);
	}

	public function delete(Graphic $graphic, $withWitness = true, $flush = true) {

		// Decrement user graphic count
		if ($graphic->getIsDraft()) {
			$graphic->getUser()->getMeta()->incrementPrivateGraphicCount(-1);
		} else {
			$graphic->getUser()->getMeta()->incrementPublicGraphicCount(-1);
		}

		parent::deletePublication($graphic, $withWitness, $flush);
	}

}