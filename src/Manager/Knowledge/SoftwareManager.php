<?php

namespace App\Manager\Knowledge;

use App\Entity\Knowledge\Software;
use App\Utils\ReviewableUtils;

class SoftwareManager extends AbstractKnowledgeManager {

	public function delete(Software $software, $withWitness = true, $flush = true) {

		// Delete reviews
		$reviewableUtils = $this->get(ReviewableUtils::class);
		$reviewableUtils->deleteReviews($software, false);

		parent::deleteKnowledge($software, $withWitness, $flush);
	}

}