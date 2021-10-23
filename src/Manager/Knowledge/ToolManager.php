<?php

namespace App\Manager\Knowledge;

use App\Entity\Knowledge\Tool;
use App\Utils\ReviewableUtils;

class ToolManager extends AbstractKnowledgeManager {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.ReviewableUtils::class,
        ));
    }

    /////

    public function delete(Tool $tool, $withWitness = true, $flush = true) {

		// Delete reviews
		$reviewableUtils = $this->get(ReviewableUtils::class);
		$reviewableUtils->deleteReviews($tool, false);

		parent::deleteKnowledge($tool, $withWitness, $flush);
	}

}