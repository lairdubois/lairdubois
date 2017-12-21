<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Workflow\Task;
use Ladb\CoreBundle\Entity\Workflow\Workflow;

class PlanUtils {

	const NAME = 'ladb_core.workflow_utils';

	public function resetTaskStatus(Workflow $workflow) {

		foreach ($workflow->getTasks() as $task) {
			if ($task->getSourceTasks()->isEmpty()) {
				$task->setStatus(Task::STATUS_WORKABLE);
			} else {
				$task->setStatus(Task::STATUS_PENDING);
			}
		}

	}

}