<?php

namespace App\Manager\Knowledge;

use App\Entity\Knowledge\Tool;
use App\Utils\ReviewableUtils;

class ToolManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.knowledge_tool_manager';

	public function delete(Tool $tool, $withWitness = true, $flush = true) {

		// Delete reviews
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$reviewableUtils->deleteReviews($tool, false);

		parent::deleteKnowledge($tool, $withWitness, $flush);
	}

}