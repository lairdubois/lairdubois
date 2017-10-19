<?php

namespace Ladb\CoreBundle\Manager\Promotion;

use Ladb\CoreBundle\Entity\Promotion\Graphic;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;

class GraphicManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.graphic_manager';

	/////

	public function publish(Graphic $graphic, $flush = true) {

		$graphic->getUser()->incrementDraftGraphicCount(-1);
		$graphic->getUser()->incrementPublishedGraphicCount();

		parent::publishPublication($graphic, $flush);
	}

	public function unpublish(Graphic $graphic, $flush = true) {

		$graphic->getUser()->incrementDraftGraphicCount(1);
		$graphic->getUser()->incrementPublishedGraphicCount(-1);

		parent::unpublishPublication($graphic, $flush);
	}

	public function delete(Graphic $graphic, $withWitness = true, $flush = true) {

		// Decrement user graphic count
		if ($graphic->getIsDraft()) {
			$graphic->getUser()->incrementDraftGraphicCount(-1);
		} else {
			$graphic->getUser()->incrementPublishedGraphicCount(-1);
		}

		parent::deletePublication($graphic, $withWitness, $flush);
	}

}