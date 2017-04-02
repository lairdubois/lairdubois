<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Entity\Workflow\Label;
use Ladb\CoreBundle\Form\Type\Workflow\LabelType;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\TagUtils;
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
 * @Route("/processus")
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

	const TASKINFO_NONE 			= 0;
	const TASKINFO_STATUS 			= 1 << 0;
	const TASKINFO_POSITION_LEFT 	= 1 << 1;
	const TASKINFO_POSITION_TOP 	= 1 << 2;
	const TASKINFO_ROW 				= 1 << 3;
	const TASKINFO_WIDGET 			= 1 << 4;
	const TASKINFO_BOX 				= 1 << 5;

	private function _generateTaskInfos($tasks = array(), $fieldsStrategy = self::TASKINFO_NONE) {
		$templating = $this->get('templating');

		if (!is_array($tasks) && !$tasks instanceof \Traversable) {
			$tasks = array( $tasks );
		}
		$taskInfos = array();
		foreach ($tasks as $task) {
			$taskInfo = array(
				'id' => $task->getId(),
			);
			if (($fieldsStrategy & self::TASKINFO_STATUS) == self::TASKINFO_STATUS) {
				$taskInfo['status'] = $task->getStatus();
			}
			if (($fieldsStrategy & self::TASKINFO_POSITION_LEFT) == self::TASKINFO_POSITION_LEFT) {
				$taskInfo['positionLeft'] = $task->getPositionLeft();
			}
			if (($fieldsStrategy & self::TASKINFO_POSITION_TOP) == self::TASKINFO_POSITION_TOP) {
				$taskInfo['positionTop'] = $task->getPositionTop();
			}
			if (($fieldsStrategy & self::TASKINFO_ROW) == self::TASKINFO_ROW) {
				$taskInfo['row'] = $templating->render('LadbCoreBundle:Workflow:_task-row.part.html.twig', array( 'task' => $task ));
			}
			if (($fieldsStrategy & self::TASKINFO_WIDGET) == self::TASKINFO_WIDGET) {
				$taskInfo['widget'] = $templating->render('LadbCoreBundle:Workflow:_task-widget.part.html.twig', array( 'task' => $task ));
			}
			if (($fieldsStrategy & self::TASKINFO_BOX) == self::TASKINFO_BOX) {
				$taskInfo['box'] = $templating->render('LadbCoreBundle:Workflow:_task-box.part.html.twig', array( 'task' => $task ));
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

	private function _push($workflow, $response) {
		$pusher = $this->get('gos_web_socket.wamp.pusher');
		$pusher->push($response, 'workflow_show_topic', array( 'id' => $workflow->getId() ));
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
	 * @Route("/new", name="core_workflow_new")
	 * @Template()
	 */
	public function newAction() {

		$workflow = new Workflow();
		$form = $this->createForm(WorkflowType::class, $workflow);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workflow),
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

			$workflow->setIsDraft(false);
			$workflow->setUser($this->getUser());

			// Append a default root task
			$task = new Task();
			$task->setTitle('Tâche 1 : Changer le monde');
			$task->setStatus(Task::STATUS_WORKABLE);
			$workflow->addTask($task);

			$om->persist($workflow);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($workflow));

			return $this->redirect($this->generateUrl('core_workflow_show', array('id' => $workflow->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'workflow'     => $workflow,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workflow),
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

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'workflow'     => $workflow,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workflow),
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

		$previouslyUsedTags = $workflow->getTags()->toArray();	// Need to be an array to copy values

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
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($workflow, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.form.alert.update_success', array( '%title%' => $workflow->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(WorkflowType::class, $workflow);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'workflow'     => $workflow,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workflow),
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
	 * @Route("/", name="core_workflow_list")
	 * @Route("/{filter}", requirements={"filter" = "\w+"}, name="core_workflow_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_workflow_list_filter_page")
	 * @Template()
	 */
	public function listAction(Request $request, $filter = 'all', $page = 0) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $workflowRepository->findPagined($offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workflow_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count(), 20, 20);

		$parameters = array(
			'filter'        => $filter,
			'prevPageUrl'   => $pageUrls->prev,
			'nextPageUrl'   => $pageUrls->next,
			'workflows'     => $paginator,
			'workflowCount' => $paginator->count(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Workflow:list-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

	/**
	 * @Route("/mes-processus", name="core_workflow_user_list")
	 * @Route("/mes-processus/{filter}", requirements={"filter" = "\w+"}, name="core_workflow_user_list_filter")
	 * @Route("/mes-processus/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_workflow_user_list_filter_page")
	 * @Template()
	 */
	public function userListAction(Request $request, $filter = 'all', $page = 0) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $workflowRepository->findPaginedByUser($this->getUser(), $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workflow_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count(), 20, 20);

		$parameters = array(
			'filter'        => $filter,
			'prevPageUrl'   => $pageUrls->prev,
			'nextPageUrl'   => $pageUrls->next,
			'workflows'     => $paginator,
			'workflowCount' => $paginator->count(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Workflow:list-xhr.html.twig', $parameters);
		}
		return $parameters;
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
		$form = $this->createForm(TaskType::class, $task, array( 'label_choices' => $this->_computeLabelChoices($workflow) ));

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
		$task->setWorkflow($workflow);
		$form = $this->createForm(TaskType::class, $task, array( 'label_choices' => $this->_computeLabelChoices($workflow) ));
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
	 * @Template("LadbCoreBundle:Workflow:task-edit-xhr.html.twig")
	 */
	public function editTaskAction(Request $request, $id) {

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
	 * @Template("LadbCoreBundle:Workflow:task-edit-xhr.html.twig")
	 */
	public function updateTaskAction(Request $request, $id) {
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

				$lastRunDuration = $now->getTimestamp() - $task->getLastRunningAt()->getTimestamp();

				// Increment duration only if it is more than a minute
				if ($lastRunDuration >= 60) {
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

		// Decrement task durations on workflow
		$workflow->incrementEstimatedDuration(-$task->getEstimatedDuration());
		$workflow->incrementDuration(-$task->getDuration());

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
	public function taskListAction(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

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
			'taskInfos'     => $this->_generateTaskInfos($workflow->getTasks(), self::TASKINFO_STATUS | self::TASKINFO_ROW | self::TASKINFO_WIDGET),
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
			$updatedTasks = $this->_updateTasksStatus(array($targetTask));
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

	/**
	 * @Route("/{id}/label/new", requirements={"id" = "\d+"}, name="core_workflow_label_new")
	 * @Template("LadbCoreBundle:Workflow:label-new-xhr.html.twig")
	 */
	public function labelNewAction(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		$label = new Label();
		$label->setColor(Label::COLOR_SEQUENCE[count($workflow->getLabels()) % count(Label::COLOR_SEQUENCE)]);
		$form = $this->createForm(LabelType::class, $label);

		return array(
			'form'     => $form->createView(),
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/{id}/label/create", requirements={"id" = "\d+"}, name="core_workflow_label_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Workflow:label-new-xhr.html.twig")
	 */
	public function labelCreateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		$label = new Label();
		$form = $this->createForm(LabelType::class, $label);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$workflow->addLabel($label);

			$om->flush();

			return $this->render('LadbCoreBundle:Workflow:label-create-xhr.html.twig', array(
				'label' => $label,
			));
		}

		return array(
			'form'     => $form->createView(),
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/label/{id}/edit", requirements={"id" = "\d+"}, name="core_workflow_label_edit")
	 * @Template("LadbCoreBundle:Workflow:label-edit-xhr.html.twig")
	 */
	public function labelEditAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$labelRepository = $om->getRepository(Label::CLASS_NAME);

		$label = $labelRepository->findOneById($id);
		if (is_null($label)) {
			throw $this->createNotFoundException('Unable to find Label entity (id='.$id.').');
		}

		$form = $this->createForm(LabelType::class, $label);

		return array(
			'form'  => $form->createView(),
			'label' => $label,
		);
	}

	/**
	 * @Route("/label/{id}/update", requirements={"id" = "\d+"}, name="core_workflow_label_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Workflow:label-edit-xhr.html.twig")
	 */
	public function labelUpdateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$labelRepository = $om->getRepository(Label::CLASS_NAME);

		$label = $labelRepository->findOneById($id);
		if (is_null($label)) {
			throw $this->createNotFoundException('Unable to find Label entity (id='.$id.').');
		}

		$form = $this->createForm(LabelType::class, $label);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$om->flush();

			// Retrieve updated tasks
			$taskRepository = $om->getRepository(Task::CLASS_NAME);
			$tasks = $taskRepository->findByLabel($label);

			// Push changes
			if (is_array($tasks) && count($tasks)) {
				$this->_push($label->getWorkflow(), array(
					'updatedTaskInfos'   => $this->_generateTaskInfos($tasks, self::TASKINFO_BOX),
				));
			}

			return $this->render('LadbCoreBundle:Workflow:label-update-xhr.html.twig', array(
				'label' => $label,
			));
		}

		return array(
			'form'  => $form->createView(),
			'label' => $label,
		);
	}

	/**
	 * @Route("/label/{id}/delete", requirements={"id" = "\d+"}, name="core_workflow_label_delete")
	 * @Template("LadbCoreBundle:Workflow:label-delete-xhr.html.twig")
	 */
	public function labelDeleteAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$labelRepository = $om->getRepository(Label::CLASS_NAME);

		$label = $labelRepository->findOneById($id);
		if (is_null($label)) {
			throw $this->createNotFoundException('Unable to find Label entity (id='.$id.').');
		}

		// Update funding balance
		$workflow = $label->getWorkflow();
		$workflow->removeLabel($label);

		// Retrieve updated tasks
		$taskRepository = $om->getRepository(Task::CLASS_NAME);
		$tasks = $taskRepository->findByLabel($label);

		$om->remove($label);
		$om->flush();

		// Push changes
		if (is_array($tasks) && count($tasks)) {
			$this->_push($workflow, array(
				'updatedTaskInfos' => $this->_generateTaskInfos($tasks, self::TASKINFO_BOX),
			));
		}

		return;
	}

	/**
	 * @Route("/{id}/labels", requirements={"id" = "\d+"}, name="core_workflow_label_list")
	 * @Template("LadbCoreBundle:Workflow:label-list-xhr.html.twig")
	 */
	public function labelListAction(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		return array(
			'workflow' => $workflow,
		);
	}

}
