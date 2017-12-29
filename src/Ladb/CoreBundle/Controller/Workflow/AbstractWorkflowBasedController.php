<?php

namespace Ladb\CoreBundle\Controller\Workflow;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ladb\CoreBundle\Entity\Workflow\Workflow;

abstract class AbstractWorkflowBasedController extends Controller {

	const TASKINFO_NONE 			= 0b000000;
	const TASKINFO_STATUS 			= 0b000001;
	const TASKINFO_POSITION_LEFT 	= 0b000010;
	const TASKINFO_POSITION_TOP 	= 0b000100;
	const TASKINFO_ROW 				= 0b001000;
	const TASKINFO_WIDGET 			= 0b010000;
	const TASKINFO_BOX 				= 0b100000;

	/////

	protected function _retrieveWorkflow($id) {
		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workflow::CLASS_NAME);

		$id = intval($id);

		$workflow = $workshopRepository->findOneById($id);
		if (is_null($workflow)) {
			throw $this->createNotFoundException('Unable to find Workflow entity (id='.$id.').');
		}

		return $workflow;
	}

	protected function _generateTaskInfos($tasks = array(), $fieldsStrategy = self::TASKINFO_NONE, $readOnly = false) {
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
				$taskInfo['row'] = $templating->render('LadbCoreBundle:Workflow:Task/_row.part.html.twig', array( 'task' => $task, 'readOnly' => $readOnly ));
			}
			if (($fieldsStrategy & self::TASKINFO_WIDGET) == self::TASKINFO_WIDGET) {
				$taskInfo['widget'] = $templating->render('LadbCoreBundle:Workflow:Task/_widget.part.html.twig', array( 'task' => $task, 'readOnly' => $readOnly ));
			}
			if (($fieldsStrategy & self::TASKINFO_BOX) == self::TASKINFO_BOX) {
				$taskInfo['box'] = $templating->render('LadbCoreBundle:Workflow:Task/_box.part.html.twig', array( 'task' => $task, 'readOnly' => $readOnly ));
			}
			$taskInfos[] = $taskInfo;
		}
		return $taskInfos;
	}

	protected function _generateWorkflowInfos($workflow) {
		$templating = $this->get('templating');

		return array(
			'statusPanel' => $templating->render('LadbCoreBundle:Workflow:_workflow-status.part.html.twig', array( 'workflow' => $workflow )),
		);
	}

	protected function _push($workflow, $response) {
		$pusher = $this->get('gos_web_socket.wamp.pusher');
		$pusher->push($response, 'workflow_show_topic', array( 'id' => $workflow->getId() ));
	}

}