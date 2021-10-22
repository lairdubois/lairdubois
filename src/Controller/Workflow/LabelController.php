<?php

namespace App\Controller\Workflow;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Workflow\Task;
use App\Entity\Workflow\Label;
use App\Form\Type\Workflow\LabelType;

/**
 * @Route("/processus")
 */
class LabelController extends AbstractWorkflowBasedController {

	/**
	 * @Route("/{id}/label/new", requirements={"id" = "\d+"}, name="core_workflow_label_new")
	 * @Template("Workflow:Label/new-xhr.html.twig")
	 */
	public function new(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		$label = new Label();
		$label->setColor(Label::COLOR_SEQUENCE[count($workflow->getLabels()) % count(Label::COLOR_SEQUENCE)]);
		$form = $this->createForm(LabelType::class, $label);

		return array(
			'form'     => $form->createView(),
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/{id}/label/create", requirements={"id" = "\d+"}, methods={"POST"}, name="core_workflow_label_create")
	 * @Template("Workflow:Label/new-xhr.html.twig")
	 */
	public function create(Request $request, $id) {

		$this->createLock('core_workflow_label_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		$label = new Label();
		$form = $this->createForm(LabelType::class, $label);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$workflow->addLabel($label);

			$om->flush();

			return $this->render('Workflow:Label/create-xhr.html.twig', array(
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
	 * @Template("Workflow:Label/edit-xhr.html.twig")
	 */
	public function edit(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$labelRepository = $om->getRepository(Label::CLASS_NAME);

		$label = $labelRepository->findOneById($id);
		if (is_null($label)) {
			throw $this->createNotFoundException('Unable to find Label entity (id='.$id.').');
		}
		$this->_assertAuthorizedWorkflow($label->getWorkflow());

		$form = $this->createForm(LabelType::class, $label);

		return array(
			'form'  => $form->createView(),
			'label' => $label,
		);
	}

	/**
	 * @Route("/label/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_workflow_label_update")
	 * @Template("Workflow:Label/edit-xhr.html.twig")
	 */
	public function update(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$labelRepository = $om->getRepository(Label::CLASS_NAME);

		$label = $labelRepository->findOneById($id);
		if (is_null($label)) {
			throw $this->createNotFoundException('Unable to find Label entity (id='.$id.').');
		}
		$this->_assertAuthorizedWorkflow($label->getWorkflow());

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

			return $this->render('Workflow:Label/update-xhr.html.twig', array(
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
	 * @Template("Workflow:Label/delete-xhr.html.twig")
	 */
	public function delete(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$labelRepository = $om->getRepository(Label::CLASS_NAME);

		$label = $labelRepository->findOneById($id);
		if (is_null($label)) {
			throw $this->createNotFoundException('Unable to find Label entity (id='.$id.').');
		}
		$this->_assertAuthorizedWorkflow($label->getWorkflow());

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
	 * @Template("Workflow:Label/list-xhr.html.twig")
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