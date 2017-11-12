<?php

namespace Ladb\CoreBundle\Controller\Workflow;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Entity\Workflow\Task;
use Ladb\CoreBundle\Entity\Workflow\Part;
use Ladb\CoreBundle\Form\Type\Workflow\PartType;

/**
 * @Route("/processus")
 */
class PartController extends AbstractWorkflowBasedController {

	/**
	 * @Route("/{id}/part/new", requirements={"id" = "\d+"}, name="core_workflow_part_new")
	 * @Template("LadbCoreBundle:Workflow:Part/new-xhr.html.twig")
	 */
	public function newAction(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		$part = new Part();
		$form = $this->createForm(PartType::class, $part);

		return array(
			'form'     => $form->createView(),
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/{id}/part/create", requirements={"id" = "\d+"}, name="core_workflow_part_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Workflow:Part/new-xhr.html.twig")
	 */
	public function createAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		$part = new Part();
		$form = $this->createForm(PartType::class, $part);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$workflow->addPart($part);

			$om->flush();

			return $this->render('LadbCoreBundle:Workflow:Part/create-xhr.html.twig', array(
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
	 * @Template("LadbCoreBundle:Workflow:Part/edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$partRepository = $om->getRepository(Part::CLASS_NAME);

		$part = $partRepository->findOneById($id);
		if (is_null($part)) {
			throw $this->createNotFoundException('Unable to find Part entity (id='.$id.').');
		}

		$form = $this->createForm(PartType::class, $part);

		return array(
			'form'  => $form->createView(),
			'part' => $part,
		);
	}

	/**
	 * @Route("/part/{id}/update", requirements={"id" = "\d+"}, name="core_workflow_part_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Workflow:Part/edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$partRepository = $om->getRepository(Part::CLASS_NAME);

		$part = $partRepository->findOneById($id);
		if (is_null($part)) {
			throw $this->createNotFoundException('Unable to find Part entity (id='.$id.').');
		}

		$form = $this->createForm(PartType::class, $part);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$om->flush();

			// Retrieve updated tasks
			$taskRepository = $om->getRepository(Task::CLASS_NAME);
			$tasks = $taskRepository->findByPart($part);

			// Push changes
			if (is_array($tasks) && count($tasks)) {
				$this->_push($part->getWorkflow(), array(
					'updatedTaskInfos'   => $this->_generateTaskInfos($tasks, self::TASKINFO_BOX),
				));
			}

			return $this->render('LadbCoreBundle:Workflow:Part/update-xhr.html.twig', array(
				'part' => $part,
			));
		}

		return array(
			'form'  => $form->createView(),
			'part' => $part,
		);
	}

	/**
	 * @Route("/part/{id}/delete", requirements={"id" = "\d+"}, name="core_workflow_part_delete")
	 * @Template("LadbCoreBundle:Workflow:Part/delete-xhr.html.twig")
	 */
	public function deleteAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$partRepository = $om->getRepository(Part::CLASS_NAME);

		$part = $partRepository->findOneById($id);
		if (is_null($part)) {
			throw $this->createNotFoundException('Unable to find Part entity (id='.$id.').');
		}

		// Update funding balance
		$workflow = $part->getWorkflow();
		$workflow->removePart($part);

		// Retrieve updated tasks
		$taskRepository = $om->getRepository(Task::CLASS_NAME);
		$tasks = $taskRepository->findByPart($part);

		$om->remove($part);
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
	 * @Template("LadbCoreBundle:Workflow:Part/list-xhr.html.twig")
	 */
	public function listAction(Request $request, $id) {

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

		return array(
			'workflow' => $workflow,
		);
	}

}