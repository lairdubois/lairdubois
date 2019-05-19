<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\Software;
use Ladb\CoreBundle\Utils\ReviewableUtils;

class SoftwareManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.software_manager';

	public function delete(Software $software, $withWitness = true, $flush = true) {

		// Delete reviews
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$reviewableUtils->deleteReviews($software, false);

		parent::deleteKnowledge($software, $withWitness, $flush);
	}

}