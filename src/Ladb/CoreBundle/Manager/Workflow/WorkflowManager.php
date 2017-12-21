<?php

namespace Ladb\CoreBundle\Manager\Workflow;

use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Entity\Core\License;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Workflow\Label;
use Ladb\CoreBundle\Entity\Workflow\Part;
use Ladb\CoreBundle\Entity\Workflow\Task;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Utils\JoinableUtils;

class WorkflowManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.workflow_manager';

	/////

	public function publish(Workflow $workflow, $flush = true) {

		$workflow->setVisibility(AbstractPublication::VISIBILITY_PUBLIC);

		parent::publishPublication($workflow, $flush);
	}

	public function unpublish(Workflow $workflow, $flush = true) {

		$workflow->setVisibility(AbstractPublication::VISIBILITY_PRIVATE);

		parent::unpublishPublication($workflow, $flush);
	}

	public function delete(Workflow $workflow, $withWitness = true, $flush = true) {

		// Unlink inspirations
		foreach ($workflow->getInspirations() as $inspiration) {
			$workflow->removeInspiration($inspiration);
		}

		parent::deletePublication($workflow, $withWitness, $flush);
	}

	public function copy(Workflow $workflow, User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$newWorkflow = new Workflow();
		$newWorkflow->setIsDraft(false);
		$newWorkflow->setVisibility(Workflow::VISIBILITY_PRIVATE);
		$newWorkflow->setUser($user);
		$newWorkflow->setTitle($workflow->getTitle().' (copie)');
		$newWorkflow->setMainPicture($workflow->getMainPicture());
		$newWorkflow->setBody($workflow->getBody());
		$newWorkflow->setHtmlBody($workflow->getHtmlBody());

		// License
		$newLicense = new License();
		$newLicense->setAllowDerivs($workflow->getLicense()->getAllowDerivs());
		$newLicense->setShareAlike($workflow->getLicense()->getShareAlike());
		$newLicense->setAllowCommercial($workflow->getLicense()->getAllowCommercial());
		$newWorkflow->setLicense($newLicense);

		// Labels
		$newLabels = array();
		foreach ($workflow->getLabels() as $label) {

			$newLabel = new Label();
			$newLabel->setName($label->getName());
			$newLabel->setColor($label->getColor());

			$newWorkflow->addLabel($newLabel);

			// Add the newly created label to a temporary array indexed on the original label id.
			$newLabels[$label->getId()] = $newLabel;

		}

		// Parts
		$newParts = array();
		foreach ($workflow->getParts() as $part) {

			$newPart = new Part();
			$newPart->setNumber($part->getNumber());
			$newPart->setName($part->getName());
			$newPart->setCount($part->getCount());

			$newWorkflow->addPart($newPart);

			// Add the newly created part to a temporary array indexed on the original part id.
			$newParts[$part->getId()] = $newPart;

		}

		// Tasks
		$newTasks = array();
		foreach ($workflow->getTasks() as $task) {		// 1st loop to generate all tasks

			$newTask = new Task();
			$newTask->setTitle($task->getTitle());
			$newTask->setPositionLeft($task->getPositionLeft());
			$newTask->setPositionTop($task->getPositionTop());
			if ($task->getSourceTasks()->isEmpty()) {
				$newTask->setStatus(Task::STATUS_WORKABLE);
			} else {
				$newTask->setStatus(Task::STATUS_PENDING);
			}
			$newTask->setPartCount($task->getPartCount());

			foreach ($task->getParts() as $part) {
				if (isset($newParts[$part->getId()])) {
					$newTask->addPart($newParts[$part->getId()]);
				}
			}

			foreach ($task->getLabels() as $label) {
				if (isset($newLabels[$label->getId()])) {
					$newTask->addLabel($newLabels[$label->getId()]);
				}
			}

			$newWorkflow->addTask($newTask);

			// Add the newly created task to a temporary array indexed on the original task id.
			$newTasks[$task->getId()] = $newTask;

		}
		foreach ($workflow->getTasks() as $task) {		// 2nd loop to build the tree

			$newTask = $newTasks[$task->getId()];
			foreach ($task->getTargetTasks() as $targetTask) {
				$newTask->addTargetTask($newTasks[$targetTask->getId()]);
			}

		}

		// Tags
		foreach ($workflow->getTags() as $tag) {
			$newWorkflow->addTag($tag);
		}

		// Inspiration
		if ($workflow->getUser()->getId() === $user->getId() && $workflow->getVisibility() < Workflow::VISIBILITY_PUBLIC) {

			// Workflow is owned and private -> juste transfert inspirations
			foreach ($workflow->getInspirations() as $inspiration) {
				$newWorkflow->addInspiration($inspiration);
			}

		} else {
			$newWorkflow->addInspiration($workflow);
		}

		$om->persist($newWorkflow);
		if ($flush) {
			$om->flush();
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($newWorkflow));

		return $newWorkflow;
	}

}