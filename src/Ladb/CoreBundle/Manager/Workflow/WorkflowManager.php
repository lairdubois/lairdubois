<?php

namespace Ladb\CoreBundle\Manager\Workflow;

use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Utils\JoinableUtils;

class WorkflowManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.workflow_manager';

	/////

	public function delete(Workflow $workflow, $withWitness = true, $flush = true) {
		parent::deletePublication($workflow, $withWitness, $flush);
	}

}