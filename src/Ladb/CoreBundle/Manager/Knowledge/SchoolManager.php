<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\School;

class SchoolManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.school_manager';

	public function delete(School $school, $withWitness = true, $flush = true) {
		parent::deleteKnowledge($school, $withWitness, $flush);
	}

}