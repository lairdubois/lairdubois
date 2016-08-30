<?php

namespace Ladb\CoreBundle\Manager\Find;

use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Utils\JoinableUtils;

class FindManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.find_manager';

	/////

	public function publish(Find $find, $flush = true) {

		$find->getUser()->incrementDraftFindCount(-1);
		$find->getUser()->incrementPublishedFindCount();

		parent::publishPublication($find, $flush);
	}

	public function unpublish(Find $find, $flush = true) {

		$find->getUser()->incrementDraftFindCount(1);
		$find->getUser()->incrementPublishedFindCount(-1);

		parent::unpublishPublication($find, $flush);
	}

	public function delete(Find $find, $withWitness = true, $flush = true) {

		// Decrement user find count
		if ($find->getIsDraft()) {
			$find->getUser()->incrementDraftFindCount(-1);
		} else {
			$find->getUser()->incrementPublishedFindCount(-1);
		}

		// Delete joins
		$joinableUtils = $this->get(JoinableUtils::NAME);
		$joinableUtils->deleteJoins($find, false);

		parent::deletePublication($find, $withWitness, $flush);
	}

}