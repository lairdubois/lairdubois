<?php

namespace App\Controller\Workflow;

use App\Entity\Workflow\Part;
use App\Entity\Workflow\Task;
use App\Form\Type\Workflow\PartType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/processus")
 */
class PartController extends AbstractWorkflowBasedController {

	/**
	 * @Route("/{id}/part/new", requirements={"id" = "\d+"}, name="core_workflow_part_new")
	 * @Template("Workflow/Part/new-xhr.html.twig")
	 */
	public function new(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		$part = new Part();
		$form = $this->createForm(PartType::class, $part);

		return array(
			'form'     => $form->createView(),
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/{id}/part/create", requirements={"id" = "\d+"}, methods={"POST"}, name="core_workflow_part_create")
	 * @Template("Workflow/Part/new-xhr.html.twig")
	 */
	public function create(Request $request, $id) {

		$this->createLock('core_workflow_part_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		$part = new Part();
		$form = $this->createForm(PartType::class, $part);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$workflow->addPart($part);

			$om->flush();

			return $this->render('Workflow/Part/create-xhr.html.twig', array(
				'part' => $part,
			));
		}

		return array(
			'form'     => $form->createView(),
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/part/{id}/edit", requirements={"id" = "\d+"}, name="core_workflow_part_edit")
	 * @Template("Workflow/Part/edit-xhr.html.twig")
	 */
	public function edit(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$partRepository = $om->getRepository(Part::class);

		$part = $partRepository->findOneById($id);
		if (is_null($part)) {
			throw $this->createNotFoundException('Unable to find Part entity (id='.$id.').');
		}
		$this->_assertAuthorizedWorkflow($part->getWorkflow());

		$form = $this->createForm(PartType::class, $part);

		return array(
			'form'  => $form->createView(),
			'part' => $part,
		);
	}

	/**
	 * @Route("/part/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_workflow_part_update")
	 * @Template("Workflow/Part/edit-xhr.html.twig")
	 */
	public function update(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$partRepository = $om->getRepository(Part::class);

		$part = $partRepository->findOneById($id);
		if (is_null($part)) {
			throw $this->createNotFoundException('Unable to find Part entity (id='.$id.').');
		}
		$this->_assertAuthorizedWorkflow($part->getWorkflow());

		$form = $this->createForm(PartType::class, $part);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$om->flush();

			// Retrieve updated tasks
			$taskRepository = $om->getRepository(Task::class);
			$tasks = $taskRepository->findByPart($part);

			// Compute parts count
			foreach ($tasks as $tmpTask) {
				$partCount = 0;
				foreach ($tmpTask->getParts() as $tmpPart) {
					$partCount += $tmpPart->getCount();
				}
				$tmpTask->setPartCount($partCount);
			}

			$om->flush();

			// Push changes
			if (is_array($tasks) && count($tasks)) {
				$this->_push($part->getWorkflow(), array(
					'updatedTaskInfos'   => $this->_generateTaskInfos($tasks, self::TASKINFO_BOX),
				));
			}

			return $this->render('Workflow/Part/update-xhr.html.twig', array(
				'part' => $part,
			));
		}

		return array(
			'form' => $form->createView(),
			'part' => $part,
		);
	}

	/**
	 * @Route("/part/{id}/delete", requirements={"id" = "\d+"}, name="core_workflow_part_delete")
	 * @Template("Workflow/Part/delete-xhr.html.twig")
	 */
	public function delete(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$partRepository = $om->getRepository(Part::class);

		$part = $partRepository->findOneById($id);
		if (is_null($part)) {
			throw $this->createNotFoundException('Unable to find Part entity (id='.$id.').');
		}
		$this->_assertAuthorizedWorkflow($part->getWorkflow());

		// Update funding balance
		$workflow = $part->getWorkflow();
		$workflow->removePart($part);

		// Retrieve updated tasks
		$taskRepository = $om->getRepository(Task::class);
		$tasks = $taskRepository->findByPart($part);

		$om->remove($part);
		$om->flush();

		// Compute parts count
		foreach ($tasks as $tmpTask) {
			$partCount = 0;
			foreach ($tmpTask->getParts() as $tmpPart) {
				$partCount += $tmpPart->getCount();
			}
			$tmpTask->setPartCount($partCount);
		}

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
	 * @Route("/{id}/parts", requirements={"id" = "\d+"}, name="core_workflow_part_list")
	 * @Template("Workflow/Part/list-xhr.html.twig")
	 */
	public function list(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		return array(
			'workflow' => $workflow,
		);
	}

}