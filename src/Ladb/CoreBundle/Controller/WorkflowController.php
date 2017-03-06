<?php

namespace Ladb\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Entity\Workflow\Task;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Form\Type\Workflow\WorkflowType;
use Ladb\CoreBundle\Form\Type\Workflow\TaskType;
use Ladb\CoreBundle\Manager\Workflow\WorkflowManager;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;

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
				"row"    => $templating->render('LadbCoreBundle:Workflow:_task-row.part.html.twig', array( 'task' => $task )),
			);
			if ($boxOnly) {
				$taskInfo["box"] = $templating->render('LadbCoreBundle:Workflow:_task-box.part.html.twig', array( 'task' => $task ));
			} else {
				$taskInfo["widget"] = $templating->render('LadbCoreBundle:Workflow:_task-widget.part.html.twig', array( 'task' => $task ));
			}
			$taskInfos[] = $taskInfo;
		}
		return $taskInfos;
	}

	private function _generateWorkflowInfos($workflow) {
		$templating = $this->get('templating');

		return array(
			'statusPanel' => $templating->render('LadbCoreBundle:Workflow:_workflow-status-panel.part.html.twig', array( 'workflow' => $workflow )),
		);
	}

	/////

	/**
	 * @Route("/new", name="core_workflow_new")
	 * @Template()
	 */
	public function newAction() {

		$workflow = new Workflow();
		$form = $this->createForm(WorkflowType::class, $workflow);

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/create", name="core_workflow_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Workflow:new.html.twig")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$workflow = new Workflow();
		$form = $this->createForm(WorkflowType::class, $workflow);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($workflow);

			$workflow->setUser($this->getUser());

			$om->persist($workflow);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($workflow));

			return $this->redirect($this->generateUrl('core_workflow_show', array('id' => $workflow->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		return array(
			'workflow' => $workflow,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_workflow_edit")
	 * @Template()
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);

		$workflow = $workflowRepository->findOneById($id);
		if (is_null($workflow)) {
			throw $this->createNotFoundException('Unable to find Workflow entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_workflow_edit)');
		}

		$form = $this->createForm(WorkflowType::class, $workflow);

		return array(
			'workflow' => $workflow,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, name="core_workflow_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Workflow:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);

		$workflow = $workflowRepository->findOneById($id);
		if (is_null($workflow)) {
			throw $this->createNotFoundException('Unable to find Workflow entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_find_update)');
		}

		$form = $this->createForm(WorkflowType::class, $workflow);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($workflow);

			if ($workflow->getUser()->getId() == $this->getUser()->getId()) {
				$workflow->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($workflow));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.form.alert.update_success', array( '%title%' => $workflow->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(WorkflowType::class, $workflow);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		return array(
			'workflow' => $workflow,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_workflow_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);

		$workflow = $workflowRepository->findOneById($id);
		if (is_null($workflow)) {
			throw $this->createNotFoundException('Unable to find Workflow entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_workflow_delete)');
		}

		// Delete
		$workflowManager = $this->get(WorkflowManager::NAME);
		$workflowManager->delete($workflow);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.form.alert.delete_success', array( '%title%' => $workflow->getTitle() )));

		return $this->redirect($this->generateUrl('core_workflow_list'));
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
			'workflow'     => $workflow,
			'form'         => $form->createView(),
			'sourceTaskId' => $request->get('sourceTaskId', 0),
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

			// Link to source task if defined
			$sourceTask = $this->_retrieveTaskFromTaskIdParam($request, 'sourceTaskId', false);
			if (!is_null($sourceTask) && !$this->_assertValidWorkflow($sourceTask, $workflow, false)) {
				$sourceTask = null;
			}
			if (!is_null($sourceTask)) {
				$sourceTask->addTargetTask($task);
			}

			$om->persist($task);
			$om->flush();

			$connections = array();
			if (!is_null($sourceTask)) {
				$connections[] = array(
					'from' => $sourceTask->getId(),
					'to'   => $task->getId(),
				);
			}

			return new JsonResponse(array(
				'success'            => true,
				'createdTaskInfos'   => $this->_generateTaskInfos(array($task), false),
				'createdConnections' => $connections,
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

			return new JsonResponse(array(
				'success'          => true,
				'updatedTaskInfos' => $this->_generateTaskInfos(array($task)),
			));
		}

		return array(
			'workflow' => $workflow,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/task/position/update", requirements={"id" = "\d+"}, name="core_workflow_task_position_update")
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

		return new JsonResponse(array(
			'success' => true,
		));
	}

	/**
	 * @Route("/{id}/task/status/update", requirements={"id" = "\d+"}, name="core_workflow_task_status_update")
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

		$newStatus = intval($request->get('status', Task::STATUS_UNKNOW));
		if ($newStatus < Task::STATUS_PENDING || $newStatus > Task::STATUS_DONE) {
			throw $this->createNotFoundException('Invalid status (status='.$newStatus.')');
		}

		$previousStatus = $task->getStatus();
		if ($newStatus != $previousStatus) {

			$now = new \DateTime();

			// The task is running -> increment duration
			if ($previousStatus == Task::STATUS_RUNNING) {

				// Increment duration
				$lastRunDuration = $now->getTimestamp() - $task->getLastRunningAt()->getTimestamp();
				$task->incrementDuration($lastRunDuration);
				$workflow->incrementDuration($lastRunDuration);
				$task->setLastRunningAt(null);

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

			$task->setStatus($newStatus);
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

		return new JsonResponse(array(
			'success'          => true,
			'workflowInfos'    => $this->_generateWorkflowInfos($workflow),
			'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks),
		));
	}

	/**
	 * @Route("/{id}/task/delete", requirements={"id" = "\d+"}, name="core_workflow_task_delete")
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

		// Decrement task duration on workflow
		$workflow->incrementDuration($task->getDuration());

		// Remove the task
		$om->remove($task);
		$om->flush();

		// Update dependant tasks status
		$updatedTasks = $this->_updateTasksStatus(array_merge($sourceTasks, $targetTasks));
		$om->flush();

		return new JsonResponse(array(
			'success'          => true,
			'workflowInfos'    => $this->_generateWorkflowInfos($workflow),
			'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks),
			'deletedTaskId'    => $taskId,
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

		// Link tasks
		$sourceTask->addTargetTask($targetTask);
		$om->flush();

		// Update dependant tasks status
		$updatedTasks = $this->_updateTasksStatus(array( $targetTask ));
		$om->flush();

		return new JsonResponse(array(
			'success'          => true,
			'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks),
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

		// Unlink tasks
		$sourceTask->removeTargetTask($targetTask);
		$om->flush();

		// Update dependant tasks status
		$updatedTasks = $this->_updateTasksStatus(array( $targetTask ));
		$om->flush();

		return new JsonResponse(array(
			'success'          => true,
			'updatedTaskInfos' => $this->_generateTaskInfos($updatedTasks),
		));
	}

}
