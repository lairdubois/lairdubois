<?php

namespace Ladb\CoreBundle\Controller\Workflow;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Entity\Workflow\Task;
use Ladb\CoreBundle\Entity\Workflow\Label;
use Ladb\CoreBundle\Form\Type\Workflow\LabelType;

/**
 * @Route("/processus")
 */
class LabelController extends AbstractWorkflowBasedController {

	/**
	 * @Route("/{id}/label/new", requirements={"id" = "\d+"}, name="core_workflow_label_new")
	 * @Template("LadbCoreBundle:Workflow:Label/new-xhr.html.twig")
	 */
	public function newAction(Request $request, $id) {

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
	 * @Template("LadbCoreBundle:Workflow:Label/new-xhr.html.twig")
	 */
	public function createAction(Request $request, $id) {

		$this->createLock('core_workflow_label_create');

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

			return $this->render('LadbCoreBundle:Workflow:Label/create-xhr.html.twig', array(
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
	 * @Template("LadbCoreBundle:Workflow:Label/edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
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
	 * @Template("LadbCoreBundle:Workflow:Label/edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
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

			return $this->render('LadbCoreBundle:Workflow:Label/update-xhr.html.twig', array(
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
	 * @Template("LadbCoreBundle:Workflow:Label/delete-xhr.html.twig")
	 */
	public function deleteAction(Request $request, $id) {
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
	 * @Template("LadbCoreBundle:Workflow:Label/list-xhr.html.twig")
	 */
	public function listAction(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);
		$this->_assertAuthorizedWorkflow($workflow);

		return array(
			'workflow' => $workflow,
		);
	}

}