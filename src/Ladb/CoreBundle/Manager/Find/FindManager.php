<?php

namespace Ladb\CoreBundle\Manager\Find;

use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Utils\JoinableUtils;

class FindManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.find_find_manager';

	/////

	public function publish(Find $find, $flush = true) {

		$find->getUser()->getMeta()->incrementPrivateFindCount(-1);
		$find->getUser()->getMeta()->incrementPublicFindCount();

		parent::publishPublication($find, $flush);
	}

	public function unpublish(Find $find, $flush = true) {

		$find->getUser()->getMeta()->incrementPrivateFindCount(1);
		$find->getUser()->getMeta()->incrementPublicFindCount(-1);

		parent::unpublishPublication($find, $flush);
	}

	public function delete(Find $find, $withWitness = true, $flush = true) {

		// Decrement user find count
		if ($find->getIsDraft()) {
			$find->getUser()->getMeta()->incrementPrivateFindCount(-1);
		} else {
			$find->getUser()->getMeta()->incrementPublicFindCount(-1);
		}

		parent::deletePublication($find, $withWitness, $flush);
	}

}