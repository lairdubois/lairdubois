<?php

namespace Ladb\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Entity\Workflow\Task;
use Ladb\CoreBundle\Form\Type\Workflow\TaskType;

/**
 * @Route("/workflow")
 */
class WorkflowController extends Controller {

	private function _retrieveWorkflow($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workflow::CLASS_NAME);

		$id = intval($id);

		$workflow = $workshopRepository->findOneById($id);
		if (is_null($workflow)) {
			throw $this->createNotFoundException('Unable to find Workflow entity (id='.$id.').');
		}

		return $workflow;
	}

	private function _retrieveTaskFromTaskIdParam(Request $request, $param = 'taskId') {
		$om = $this->getDoctrine()->getManager();
		$taskRepository = $om->getRepository(Task::CLASS_NAME);

		$taskId = intval($request->get($param));

		$task = $taskRepository->findOneById($taskId);
		if (is_null($task)) {
			throw $this->createNotFoundException('Unable to find Task entity (id='.$taskId.').');
		}

		return $task;
	}

	private function _assertValidWorkflow(Task $task, Workflow $workflow) {
		if ($task->getWorkflow() != $workflow) {
			throw $this->createNotFoundException('Wrong Workflow (id='.$workflow->getId().').');
		}

		return true;
	}

	private function _updateTasksStatus($tasks = array()) {
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

	private function _generateTaskInfos($tasks, $boxOnly = true) {
		$templating = $this->get('templating');

		$taskInfos = array();
		foreach ($tasks as $task) {
			$taskInfo = array(
				"id"     => $task->getId(),
				"status" => $task->getStatus(),
				"row"    => $templating->render('LadbCoreBundle:Workflow:_task-row.part.html.twig', array('task' => $task)),
			);
			if ($boxOnly) {
				$taskInfo["box"] = $templating->render('LadbCoreBundle:Workflow:_task-box.part.html.twig', array('task' => $task));
			} else {
				$taskInfo["widget"] = $templating->render('LadbCoreBundle:Workflow:_task-widget.part.html.twig', array('task' => $task));
			}
			$taskInfos[] = $taskInfo;
		}
		return $taskInfos;
	}

	/////

	/**
	 * @Route("/new", name="core_workflow_new")
	 * @Template()
	 */
	public function newAction() {
		$om = $this->getDoctrine()->getManager();

		$workflow = new Workflow();
		$workflow->setTitle('SansTitre');
		$workflow->setUser($this->getUser());

		$om->persist($workflow);
		$om->flush();

		return $this->redirect($this->generateUrl('core_workflow_show', array('id' => $workflow->getId())));
	}

	/**
	 * @Route("/{id}.html", name="core_workflow_show")
	 * @Template()
	 */
	public function showAction($id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		return array(
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/{id}/task/new", requirements={"id" = "\d+"}, name="core_workflow_task_new")
	 * @Template("LadbCoreBundle:Workflow:task-new-xhr.html.twig")
	 */
	public function newTaskAction(Request $request, $id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		$task = new Task();
		$task->setPositionLeft(intval($request->get('positionLeft', 0)));
		$task->setPositionTop(intval($request->get('positionTop', 0)));
		$form = $this->createForm(TaskType::class, $task);

		return array(
			'workflow' => $workflow,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/task/create", requirements={"id" = "\d+"}, name="core_workflow_task_create")
	 * @Template("LadbCoreBundle:Workflow:task-new-xhr.html.twig")
	 */
	public function createTaskAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		$task = new Task();
		$form = $this->createForm(TaskType::class, $task);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$task->setStatus(Task::STATUS_WORKABLE);
			$workflow->addTask($task);

			$om->persist($task);
			$om->flush();

			return $this->render('LadbCoreBundle:Workflow:task-create-xhr.json.twig', array(
				'response' => array(
					'success' => true,
					'createdTaskInfos' => $this->_generateTaskInfos(array( $task ), false),
				),
			));
		}

		return array(
			'workflow' => $workflow,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/task/edit", requirements={"id" = "\d+"}, name="core_workflow_task_edit")
	 * @Template("LadbCoreBundle:Workflow:task-edit-xhr.html.twig")
	 */
	public function editTaskAction(Request $request, $id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		$form = $this->createForm(TaskType::class, $task);

		return array(
			'workflow' => $workflow,
			'task'     => $task,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/task/update", requirements={"id" = "\d+"}, name="core_workflow_task_update")
	 * @Template("LadbCoreBundle:Workflow:task-edit-xhr.html.twig")
	 */
	public function updateTaskAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		$form = $this->createForm(TaskType::class, $task);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$om->flush();

			return $this->render('LadbCoreBundle:Workflow:task-update-xhr.json.twig', array(
				'response' => array(
					'success' => true,
					'updatedTaskInfos' => $this->_generateTaskInfos(array( $task )),
				),
			));
		}

		return array(
			'workflow' => $workflow,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/task/position/update", requirements={"id" = "\d+"}, name="core_workflow_task_position_update")
	 * @Template("LadbCoreBundle:Workflow:task-update-xhr.json.twig")
	 */
	public function positionUpdateTaskAction(Request $request, $id) {
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

		return array(
			'response' => array(
				'success' => true,
			),
		);
	}

	/**
	 * @Route("/{id}/task/status/update", requirements={"id" = "\d+"}, name="core_workflow_task_status_update")
	 * @Template("LadbCoreBundle:Workflow:task-update-xhr.json.twig")
	 */
	public function statusUpdateTaskAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		// Status
		$statusChanged = false;
		if ($request->request->has('status')) {
			$status = intval($request->request->get('status'));
			$task->setStatus($status);
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

		return array(
			'response' => array(
				'success' => true,
				'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks),
			),
		);
	}

	/**
	 * @Route("/{id}/task/delete", requirements={"id" = "\d+"}, name="core_workflow_task_delete")
	 * @Template("LadbCoreBundle:Workflow:task-delete-xhr.json.twig")
	 */
	public function deleteTaskAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		// Retieve Task
		$task = $this->_retrieveTaskFromTaskIdParam($request);
		$this->_assertValidWorkflow($task, $workflow);

		$taskId = $task->getId();
		$sourceTasks = $task->getSourceTasks()->toArray();
		$targetTasks = $task->getTargetTasks()->toArray();

		// Remove the task
		$om->remove($task);
		$om->flush();

		// Update dependant tasks status
		$updatedTasks = $this->_updateTasksStatus(array_merge($sourceTasks, $targetTasks));
		$om->flush();

		return array(
			'response' => array(
				'success' => true,
				'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks),
				'deletedTaskId' => $taskId,
			),
		);
	}

	/**
	 * @Route("/{id}/task/connection/create", requirements={"id" = "\d+"}, name="core_workflow_task_connection_create")
	 * @Template("LadbCoreBundle:Workflow:task-connection-create-xhr.json.twig")
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

		// Link tasks
		$sourceTask->addTargetTask($targetTask);
		$om->flush();

		// Update dependant tasks status
		$updatedTasks = $this->_updateTasksStatus(array( $targetTask ));
		$om->flush();

		return array(
			'response' => array(
				'success' => true,
				'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks),
			),
		);
	}

	/**
	 * @Route("/{id}/task/connection/delete", requirements={"id" = "\d+"}, name="core_workflow_task_connection_delete")
	 * @Template("LadbCoreBundle:Workflow:task-connection-delete-xhr.json.twig")
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

		// Unlink tasks
		$sourceTask->removeTargetTask($targetTask);
		$om->flush();

		// Update dependant tasks status
		$updatedTasks = $this->_updateTasksStatus(array( $targetTask ));
		$om->flush();

		return array(
			'response' => array(
				'success' => true,
				'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks),
			),
		);
	}

}
