<?php

namespace App\Controller\Workflow;

use App\Entity\Workflow\Task;
use App\Entity\Workflow\Workflow;
use App\Form\Type\Workflow\TaskType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
				if ($task->getStatus() == Task::STATUS_RUNNING) {
					$this->_finishTaskCurrentRun($task, $task->getWorkflow(), new \DateTime());
				}
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

	private function _computePartCount(Task $task) {
		$partCount = 0;
		foreach ($task->getParts() as $tmpPart) {
			$partCount += $tmpPart->getCount();
		}
		$task->setPartCount($partCount);
	}

	private function _finishTaskCurrentRun(Task $task, Workflow $workflow, $now) {

		$currentRun = $task->getRuns()->last();
		if (!is_null($currentRun)) {

			// Finish the run
			$currentRun->setFinishedAt($now);

			// Compute run duration in minutes
			$currentRunDuration = $now->getTimestamp() - $currentRun->getStartedAt()->getTimestamp();
			$currentRunDuration = floor($currentRunDuration / 60) * 60; // Sample to minutes

			if ($currentRunDuration > 0) { // only if it is more than a minute

				// Increment duration
				$task->incrementDuration($currentRunDuration);
				$workflow->incrementDuration($currentRunDuration);

			}

		}

	}

	/////

	/**
	 * @Route("/{id}/task/new", requirements={"id" = "\d+"}, name="core_workflow_task_new")
	 * @Template("Workflow:Task/new-xhr.html.twig")
	 */
	public function new(Request $request, $id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

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
	 * @Template("Workflow:Task/new-xhr.html.twig")
	 */
	public function create(Request $request, $id) {

		$this->createLock('core_workflow_task_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		$task = new Task();
		$task->setWorkflow($workflow);
		$form = $this->createForm(TaskType::class, $task, array( 'label_choices' => $this->_computeLabelChoices($workflow) ));
		$form->handleRequest($request);

		if ($form->isValid()) {

			$task->setStatus(Task::STATUS_WORKABLE);
			$workflow->addTask($task);
			$workflow->incrementTaskCount();

			// Flag workflow as updated
			$workflow->setUpdatedAt(new \DateTime());

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

			// Compute parts count
			$this->_computePartCount($task);

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
	 * @Template("Workflow:Task/edit-xhr.html.twig")
	 */
	public function edit(Request $request, $id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

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
	 * @Template("Workflow:Task/edit-xhr.html.twig")
	 */
	public function update(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		$previousEstimatedDuration = $task->getEstimatedDuration();
		$previousDuration = $task->getDuration();

		$form = $this->createForm(TaskType::class, $task, array( 'label_choices' => $this->_computeLabelChoices($workflow) ));
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Flag workflow as updated
			$workflow->setUpdatedAt(new \DateTime());

			$parameters = array();

			$newEstimatedDuration = $task->getEstimatedDuration();
			if ($newEstimatedDuration != $previousEstimatedDuration) {

				// Update workflow estimated duration
				$workflow->incrementEstimatedDuration($task->getEstimatedDuration() - $previousEstimatedDuration);

			}

			$newDuration = $task->getDuration();
			if ($newDuration != $previousDuration) {

				// Update workflow duration
				$workflow->incrementDuration($task->getDuration() - $previousDuration);

			}

			// Compute parts count
			$this->_computePartCount($task);

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
	public function positionUpdate(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$taskRepository = $om->getRepository(Task::CLASS_NAME);

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		$tasks = array();
		$index = 1;
		while (true) {

			try {

				// Retieve Task
				$task = $this->_retrieveTaskFromTaskIdParam($request, 'taskId'.$index);
				$this->_assertValidWorkflow($task, $workflow);

				$tasks[] = $task;

			} catch (\Exception $e) {
				// Parameter do not exists => break loop
				break;
			}

			// PositionLeft
			$positionLeftKey = 'positionLeft'.$index;
			if ($request->request->has($positionLeftKey)) {
				$positionLeft = intval($request->request->get($positionLeftKey));
				$task->setPositionLeft($positionLeft);
			}

			// PositionTop
			$positionTopKey = 'positionTop'.$index;
			if ($request->request->has($positionTopKey)) {
				$positionTop = intval($request->request->get($positionTopKey));
				$task->setPositionTop($positionTop);
			}

			$index++;
		}

		if (count($tasks) == 0) {
			throw $this->createNotFoundException('Unable to find Task entities.');
		}

		// Flag workflow as updated
		$workflow->setUpdatedAt(new \DateTime());

		$om->flush();

		$this->_push($workflow, array(
			'movedTaskInfos' => $this->_generateTaskInfos($tasks, self::TASKINFO_POSITION_LEFT | self::TASKINFO_POSITION_TOP | self::TASKINFO_SORT_INDEX),
		));

		return new JsonResponse(array(
			'success' => true,
		));
	}

	/**
	 * @Route("/{id}/task/status/update", requirements={"id" = "\d+"}, name="core_workflow_task_status_update")
	 */
	public function statusUpdate(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

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

			// The task was running -> increment duration
			if ($previousStatus == Task::STATUS_RUNNING) {

				$this->_finishTaskCurrentRun($task, $workflow, $now);

			}

			// The task was done -> unset finishedAt
			if ($previousStatus == Task::STATUS_DONE) {

				if ($task->getDuration() == 0) {
					$task->setStartedAt(null);
				}
				$task->setFinishedAt(null);

			}

			// The task will run -> create a run
			if ($newStatus == Task::STATUS_RUNNING) {

				if ($task->getDuration() == 0) {
					$task->setStartedAt($now);
				}

				$run = new \ladb\CoreBundle\Entity\Workflow\Run();
				$run->setStartedAt($now);
				$task->addRun($run);

			}

			// The task will be done -> set finishedAt
			if ($newStatus == Task::STATUS_DONE) {

				if (is_null($task->getStartedAt())) {
					$task->setStartedAt($now);
				}
				$task->setFinishedAt($now);

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
	public function delete(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

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

		// Flag workflow as updated
		$workflow->setUpdatedAt(new \DateTime());

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
	public function list(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retrieve readOnly parameter
		$readOnly = $request->get('readOnly', false);

		// Compute durationHidden parameter
		$durationsHidden = !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $this->getUser() != $workflow->getUser();

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
			'taskInfos'     => $this->_generateTaskInfos($workflow->getTasks(), self::TASKINFO_STATUS | self::TASKINFO_ROW | self::TASKINFO_WIDGET, $readOnly, $durationsHidden),
			'connections'   => $connections
		));
	}

	/**
	 * @Route("/{id}/task/connection/create", requirements={"id" = "\d+"}, name="core_workflow_task_connection_create")
	 */
	public function createTaskConnection(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		// Retieve source Task
		$sourceTask = $this->_retrieveTaskFromTaskIdParam($request, 'sourceTaskId');
		$this->_assertValidWorkflow($sourceTask, $workflow);

		// Retieve target Task
		$targetTask = $this->_retrieveTaskFromTaskIdParam($request, 'targetTaskId');
		$this->_assertValidWorkflow($targetTask, $workflow);

		// Check if connection exists
		if (!$sourceTask->getTargetTasks()->contains($targetTask)) {

			// Flag workflow as updated
			$workflow->setUpdatedAt(new \DateTime());

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
	public function deleteTaskConnection(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

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

		// Flag workflow as updated
		$workflow->setUpdatedAt(new \DateTime());

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