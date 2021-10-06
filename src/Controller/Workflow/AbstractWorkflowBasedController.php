<?php

namespace App\Controller\Workflow;

use App\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\Workflow\Workflow;

abstract class AbstractWorkflowBasedController extends AbstractController {

	const TASKINFO_NONE 			= 0b0000000;
	const TASKINFO_STATUS 			= 0b0000001;
	const TASKINFO_POSITION_LEFT 	= 0b0000010;
	const TASKINFO_POSITION_TOP 	= 0b0000100;
	const TASKINFO_SORT_INDEX	 	= 0b0001000;
	const TASKINFO_ROW 				= 0b0010000;
	const TASKINFO_WIDGET 			= 0b0100000;
	const TASKINFO_BOX 				= 0b1000000;

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

	protected function _assertAuthorizedWorkflow(Workflow $workflow) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser() != $this->getUser()) {
			throw $this->createNotFoundException('Not allowed');
		}
	}

	protected function _generateTaskInfos($tasks = array(), $fieldsStrategy = self::TASKINFO_NONE, $readOnly = false, $durationsHidden = false) {
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
			if (($fieldsStrategy & self::TASKINFO_SORT_INDEX) == self::TASKINFO_SORT_INDEX) {
				$taskInfo['sortIndex'] = $task->getPositionTop();
			}
			if (($fieldsStrategy & self::TASKINFO_ROW) == self::TASKINFO_ROW) {
				$taskInfo['row'] = $templating->render('Workflow:Task/_row.part.html.twig', array( 'task' => $task, 'readOnly' => $readOnly, 'durationsHidden' => $durationsHidden ));
			}
			if (($fieldsStrategy & self::TASKINFO_WIDGET) == self::TASKINFO_WIDGET) {
				$taskInfo['widget'] = $templating->render('Workflow:Task/_widget.part.html.twig', array( 'task' => $task, 'readOnly' => $readOnly, 'durationsHidden' => $durationsHidden ));
			}
			if (($fieldsStrategy & self::TASKINFO_BOX) == self::TASKINFO_BOX) {
				$taskInfo['box'] = $templating->render('Workflow:Task/_box.part.html.twig', array( 'task' => $task, 'readOnly' => $readOnly, 'durationsHidden' => $durationsHidden ));
			}
			$taskInfos[] = $taskInfo;
		}
		return $taskInfos;
	}

	protected function _generateWorkflowInfos($workflow) {
		$templating = $this->get('templating');

		return array(
			'statusPanel' => $templating->render('Workflow:Workflow/_workflow-status.part.html.twig', array( 'workflow' => $workflow )),
		);
	}

	protected function _push($workflow, $response) {
		$pusher = $this->get('gos_web_socket.wamp.pusher');
		$pusher->push($response, 'workflow_show_topic', array( 'id' => $workflow->getId() ));
	}

}