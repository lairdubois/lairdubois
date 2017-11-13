<?php

namespace Ladb\CoreBundle\Controller\Workflow;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Entity\Workflow\Task;
use Ladb\CoreBundle\Form\Type\Workflow\TaskType;

/**
 * @Route("/processus")
 */
class TaskController extends AbstractWorkflowBasedController {

	private function _assertValidWorkflow(Task $task, Workflow $workflow, $wrongWorkflowException = true) {
		if ($task->getWorkflow() != $workflow) {
			if ($wrongWorkflowException) {
				throw $this->createNotFoundException('Wrong Workflow (id='.$workflow->getId().').');
			} else {
				return false;
			}
		}

		return true;
	}

	private function _retrieveTaskFromTaskIdParam(Request $request, $param = 'taskId', $notFoundException = true) {
		$om = $this->getDoctrine()->getManager();
		$taskRepository = $om->getRepository(Task::CLASS_NAME);

		$taskId = intval($request->get($param, 0));

		$task = $taskRepository->findOneById($taskId);
		if (is_null($task) && $notFoundException) {
			throw $this->createNotFoundException('Unable to find Task entity (id='.$taskId.').');
		}

		return $task;
	}

	private function _updateTasksStatus($tasks = array()) {
		if (!is_array($tasks)) {
			$tasks = array( $tasks );
		}
		$updatedTask = array();
		foreach ($tasks as $task) {

			if ($task->getStatus() == Task::STATUS_DONE) {
				continue;
			}

			$isWorkable = true;
			foreach ($task->getSourceTasks() as $sourceTask) {
				if ($sourceTask->getStatus() != Task::STATUS_DONE) {
					$isWorkable = false;
					break;
				}
			}

			$newStatus = $isWorkable ? Task::STATUS_WORKABLE : Task::STATUS_PENDING;
			if ($task->getStatus() != $newStatus) {
				$task->setStatus($newStatus);
				$updatedTask[] = $task;
			}

		}
		return $updatedTask;
	}

	private function _computeLabelChoices(Workflow $workflow) {
		$labelChoices = array();
		foreach ($workflow->getLabels() as $label) {
			$labelChoices[$label->getName()] = $label->getId();
		}
		return $labelChoices;
	}

	/////

	/**
	 * @Route("/{id}/task/new", requirements={"id" = "\d+"}, name="core_workflow_task_new")
	 * @Template("LadbCoreBundle:Workflow:Task/new-xhr.html.twig")
	 */
	public function newAction(Request $request, $id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		$task = new Task();
		$task->setPositionLeft(intval($request->get('positionLeft', 0)));
		$task->setPositionTop(intval($request->get('positionTop', 0)));
		$form = $this->createForm(TaskType::class, $task, array( 'label_choices' => $this->_computeLabelChoices($workflow) ));

		return array(
			'workflow'     => $workflow,
			'form'         => $form->createView(),
			'sourceTaskId' => $request->get('sourceTaskId', 0),
		);
	}

	/**
	 * @Route("/{id}/task/create", requirements={"id" = "\d+"}, name="core_workflow_task_create")
	 * @Template("LadbCoreBundle:Workflow:Task/new-xhr.html.twig")
	 */
	public function createAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		$task = new Task();
		$task->setWorkflow($workflow);
		$form = $this->createForm(TaskType::class, $task, array( 'label_choices' => $this->_computeLabelChoices($workflow) ));
		$form->handleRequest($request);

		if ($form->isValid()) {

			$task->setStatus(Task::STATUS_WORKABLE);
			$workflow->addTask($task);
			$workflow->incrementTaskCount();

			// Link to source task if defined
			$sourceTask = $this->_retrieveTaskFromTaskIdParam($request, 'sourceTaskId', false);
			if (!is_null($sourceTask) && !$this->_assertValidWorkflow($sourceTask, $workflow, false)) {
				$sourceTask = null;
			}
			if (!is_null($sourceTask)) {
				$sourceTask->addTargetTask($task);
				if ($sourceTask->getStatus() != Task::STATUS_DONE) {
					$task->setStatus(Task::STATUS_PENDING);
				}
			}

			if ($task->getEstimatedDuration() > 0) {

				// Update workflow estimated duration
				$workflow->incrementEstimatedDuration($task->getEstimatedDuration());

			}

			if ($task->getDuration() > 0) {

				// Update workflow duration
				$workflow->incrementDuration($task->getDuration());

			}

			$om->persist($task);
			$om->flush();

			$parameters = array();

			if ($task->getEstimatedDuration() > 0 || $task->getDuration() > 0) {

				$parameters = array_merge($parameters, array(
					'workflowInfos' => $this->_generateWorkflowInfos($workflow),
				));

			}

			if (!is_null($sourceTask)) {

				$parameters = array_merge($parameters, array(
					'createdConnections' => array(array(
						'from' => $sourceTask->getId(),
						'to'   => $task->getId(),
					)),
				));

			}

			$this->_push($workflow, array_merge($parameters, array(
				'createdTaskInfos' => $this->_generateTaskInfos($task, self::TASKINFO_STATUS | self::TASKINFO_ROW | self::TASKINFO_WIDGET),
			)));

			return new JsonResponse(array(
				'success' => true,
			));
		}

		return array(
			'workflow'     => $workflow,
			'form'         => $form->createView(),
			'sourceTaskId' => $request->get('sourceTaskId', 0),
		);
	}

	/**
	 * @Route("/{id}/task/edit", requirements={"id" = "\d+"}, name="core_workflow_task_edit")
	 * @Template("LadbCoreBundle:Workflow:Task/edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		$form = $this->createForm(TaskType::class, $task, array( 'label_choices' => $this->_computeLabelChoices($workflow) ));

		return array(
			'workflow' => $workflow,
			'task'     => $task,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/task/update", requirements={"id" = "\d+"}, name="core_workflow_task_update")
	 * @Template("LadbCoreBundle:Workflow:Task/edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		$previousEstimatedDuration = $task->getEstimatedDuration();
		$previousDuration = $task->getDuration();

		$form = $this->createForm(TaskType::class, $task, array( 'label_choices' => $this->_computeLabelChoices($workflow) ));
		$form->handleRequest($request);

		if ($form->isValid()) {

			$parameters = array();

			$newEstimatedDuration = $task->getEstimatedDuration();
			if ($newEstimatedDuration != $previousEstimatedDuration) {

				// Update workflow estimated duration
				$workflow->incrementEstimatedDuration(-$previousEstimatedDuration);
				$workflow->incrementEstimatedDuration($task->getEstimatedDuration());

			}

			$newDuration = $task->getDuration();
			if ($newDuration != $previousDuration) {

				// Update workflow duration
				$workflow->incrementDuration(-$previousDuration);
				$workflow->incrementDuration($task->getDuration());

			}

			$om->flush();

			if ($newEstimatedDuration != $previousEstimatedDuration || $newDuration != $previousDuration) {

				$parameters = array_merge($parameters, array(
					'workflowInfos' => $this->_generateWorkflowInfos($workflow),
				));

			}

			$this->_push($workflow, array_merge($parameters, array(
				'updatedTaskInfos' => $this->_generateTaskInfos($task, self::TASKINFO_STATUS | self::TASKINFO_BOX),
			)));

			return new JsonResponse(array(
				'success' => true,
			));
		}

		return array(
			'workflow' => $workflow,
			'task'     => $task,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/task/position/update", requirements={"id" = "\d+"}, name="core_workflow_task_position_update")
	 */
	public function positionUpdateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		// PositionLeft
		if ($request->request->has('positionLeft')) {
			$positionLeft = intval($request->request->get('positionLeft'));
			$task->setPositionLeft($positionLeft);
		}

		// PositionTop
		if ($request->request->has('positionTop')) {
			$positionTop = intval($request->request->get('positionTop'));
			$task->setPositionTop($positionTop);
		}

		$om->flush();

		$this->_push($workflow, array(
			'movedTaskInfos' => $this->_generateTaskInfos($task, self::TASKINFO_POSITION_LEFT | self::TASKINFO_POSITION_TOP),
		));

		return new JsonResponse(array(
			'success' => true,
		));
	}

	/**
	 * @Route("/{id}/task/status/update", requirements={"id" = "\d+"}, name="core_workflow_task_status_update")
	 */
	public function statusUpdateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		// Status
		$statusChanged = false;

		$newStatus = intval($request->get('status', Task::STATUS_UNKNOW));
		if ($newStatus < Task::STATUS_PENDING || $newStatus > Task::STATUS_DONE) {
			throw $this->createNotFoundException('Invalid status (status='.$newStatus.')');
		}

		$previousStatus = $task->getStatus();
		if ($newStatus != $previousStatus) {

			$now = new \DateTime();

			// The task is running -> increment duration
			if ($previousStatus == Task::STATUS_RUNNING) {

				$lastRunDuration = $now->getTimestamp() - $task->getLastRunningAt()->getTimestamp();
				$lastRunDuration = floor($lastRunDuration / 60) * 60; // Sample to minutes

				// Increment duration only if it is more than a minute
				if ($lastRunDuration > 0) {
					$task->incrementDuration($lastRunDuration);
					$workflow->incrementDuration($lastRunDuration);
					$task->setLastRunningAt(null);
				}

			}

			// The task is done -> unset finishedAt
			if ($previousStatus == Task::STATUS_DONE) {

				if ($task->getDuration() == 0) {
					$task->setStartedAt(null);
				}
				$task->setFinishedAt(null);

			}

			// The task will run -> set start dates
			if ($newStatus == Task::STATUS_RUNNING) {

				if ($task->getDuration() == 0) {
					$task->setStartedAt($now);
				}
				$task->setLastRunningAt($now);

			}

			// The task done -> set finishedAt
			if ($newStatus == Task::STATUS_DONE) {

				if (is_null($task->getStartedAt())) {
					$task->setStartedAt($now);
				}
				$task->setFinishedAt($now);
				$task->setLastRunningAt(null);

			}

			if ($task->getStatus() == Task::STATUS_DONE) {
				$workflow->incrementDoneTaskCount(-1);
			} else if ($task->getStatus() == Task::STATUS_RUNNING) {
				$workflow->incrementRunningTaskCount(-1);
			}
			$task->setStatus($newStatus);
			if ($task->getStatus() == Task::STATUS_DONE) {
				$workflow->incrementDoneTaskCount();
			} else if ($task->getStatus() == Task::STATUS_RUNNING) {
				$workflow->incrementRunningTaskCount();
			}
			$statusChanged = true;

		}

		$om->flush();

		if ($statusChanged) {

			// Update dependant tasks status
			$updatedTasks = array_merge(array( $task ), $this->_updateTasksStatus($task->getTargetTasks()->toArray()));
			$om->flush();

		} else {
			$updatedTasks = array();
		}

		$this->_push($workflow, array(
			'workflowInfos'    => $this->_generateWorkflowInfos($workflow),
			'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks, self::TASKINFO_STATUS | self::TASKINFO_BOX),
		));

		return new JsonResponse(array(
			'success' => true,
		));
	}

	/**
	 * @Route("/{id}/task/delete", requirements={"id" = "\d+"}, name="core_workflow_task_delete")
	 */
	public function deleteAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		$taskId = $task->getId();
		$sourceTasks = $task->getSourceTasks()->toArray();
		$targetTasks = $task->getTargetTasks()->toArray();

		// Decrement task durations on workflow
		$workflow->incrementEstimatedDuration(-$task->getEstimatedDuration());
		$workflow->incrementDuration(-$task->getDuration());

		$workflow->incrementTaskCount(-1);
		if ($task->getStatus() == Task::STATUS_DONE) {
			$workflow->incrementDoneTaskCount(-1);
		} else if ($task->getStatus() == Task::STATUS_RUNNING) {
			$workflow->incrementRunningTaskCount(-1);
		}

		// Remove the task
		$om->remove($task);
		$om->flush();

		// Update dependant tasks status
		$updatedTasks = $this->_updateTasksStatus(array_merge($sourceTasks, $targetTasks));
		$om->flush();

		$this->_push($workflow, array(
			'workflowInfos'    => $this->_generateWorkflowInfos($workflow),
			'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks, self::TASKINFO_STATUS | self::TASKINFO_BOX),
			'deletedTaskId'    => $taskId,
		));

		return new JsonResponse(array(
			'success' => true,
		));
	}

	/**
	 * @Route("/{id}/tasks", requirements={"id" = "\d+"}, name="core_workflow_task_list")
	 */
	public function listAction(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retrieve readOnly parameter
		$readOnly = $request->get('readOnly', false);

		$connections = array();
		foreach ($workflow->getTasks() as $sourceTask) {
			foreach ($sourceTask->getTargetTasks() as $targetTask) {
				$connections[] = array(
					'from' => $sourceTask->getId(),
					'to'   => $targetTask->getId(),
				);
			}
		}

		return new JsonResponse(array(
			'success'       => true,
			'workflowInfos' => $this->_generateWorkflowInfos($workflow),
			'taskInfos'     => $this->_generateTaskInfos($workflow->getTasks(), self::TASKINFO_STATUS | self::TASKINFO_ROW | self::TASKINFO_WIDGET, $readOnly),
			'connections'   => $connections
		));
	}

	/**
	 * @Route("/{id}/task/connection/create", requirements={"id" = "\d+"}, name="core_workflow_task_connection_create")
	 */
	public function createTaskConnectionAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve source Task
		$sourceTask = $this->_retrieveTaskFromTaskIdParam($request, 'sourceTaskId');
		$this->_assertValidWorkflow($sourceTask, $workflow);

		// Retieve target Task
		$targetTask = $this->_retrieveTaskFromTaskIdParam($request, 'targetTaskId');
		$this->_assertValidWorkflow($targetTask, $workflow);

		// Check if connection exists
		if (!$sourceTask->getTargetTasks()->contains($targetTask)) {

			// Link tasks
			$sourceTask->addTargetTask($targetTask);
			$om->flush();

			// Update dependant tasks status
			$updatedTasks = $this->_updateTasksStatus(array( $targetTask ));
			$om->flush();

			$createdConnections = array(
				array(
					'from' => $sourceTask->getId(),
					'to'   => $targetTask->getId(),
				)
			);

			$this->_push($workflow, array(
				'createdConnections' => $createdConnections,
				'updatedTaskInfos'   => $this->_generateTaskInfos($updatedTasks, self::TASKINFO_STATUS | self::TASKINFO_BOX),
			));

		}

		return new JsonResponse(array(
			'success' => true,
		));
	}

	/**
	 * @Route("/{id}/task/connection/delete", requirements={"id" = "\d+"}, name="core_workflow_task_connection_delete")
	 */
	public function deleteTaskConnectionAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve source Task
		$sourceTask = $this->_retrieveTaskFromTaskIdParam($request, 'sourceTaskId');
		$this->_assertValidWorkflow($sourceTask, $workflow);

		// Retieve target Task
		$targetTask = $this->_retrieveTaskFromTaskIdParam($request, 'targetTaskId');
		$this->_assertValidWorkflow($targetTask, $workflow);

		$deletedConnections = array(
			array(
				'from' => $sourceTask->getId(),
				'to'   => $targetTask->getId(),
			)
		);

		// Unlink tasks
		$sourceTask->removeTargetTask($targetTask);
		$om->flush();

		// Update dependant tasks status
		$updatedTasks = $this->_updateTasksStatus(array( $targetTask ));
		$om->flush();

		$this->_push($workflow, array(
			'deletedConnections' => $deletedConnections,
			'updatedTaskInfos'   => $this->_generateTaskInfos($updatedTasks, self::TASKINFO_STATUS | self::TASKINFO_BOX),
		));

		return new JsonResponse(array(
			'success' => true,
		));
	}

}