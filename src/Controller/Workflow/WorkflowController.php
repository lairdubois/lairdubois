<?php

namespace App\Controller\Workflow;

use App\Controller\PublicationControllerTrait;
use App\Utils\CollectionnableUtils;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Form\Type\Workflow\WorkflowType;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\TagUtils;
use App\Utils\CommentableUtils;
use App\Utils\FollowerUtils;
use App\Utils\LikableUtils;
use App\Utils\SearchUtils;
use App\Utils\WatchableUtils;
use App\Entity\Workflow\Workflow;
use App\Entity\Workflow\Task;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Manager\Workflow\WorkflowManager;
use App\Manager\Core\WitnessManager;
use App\Entity\Howto\Howto;
use App\Entity\Wonder\Creation;
use App\Entity\Wonder\Plan;
use App\Entity\Wonder\Workshop;
use App\Model\HiddableInterface;
use App\Utils\PaginatorUtils;

/**
 * @Route("/processus")
 */
class WorkflowController extends AbstractWorkflowBasedController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_workflow_new")
	 * @Template("Workflow/Workflow/new.html.twig")
	 */
	public function new(Request $request) {

		$workflow = new Workflow();
		$form = $this->createForm(WorkflowType::class, $workflow);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'owner'        => $this->retrieveOwner($request),
			'tagProposals' => $tagUtils->getProposals($workflow),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_workflow_create")
	 * @Template("Workflow/Workflow/new.html.twig")
	 */
	public function create(Request $request) {

		$owner = $this->retrieveOwner($request);

		$this->createLock('core_workflow_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$workflow = new Workflow();
		$form = $this->createForm(WorkflowType::class, $workflow);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($workflow);

			$workflow->setUser($owner);
			$owner->getMeta()->incrementPrivateWorkflowCount();

			// Append a default root task
			$task = new Task();
			$task->setTitle('Tâche 1 : Changer le monde');
			$task->setStatus(Task::STATUS_WORKABLE);
			$workflow->addTask($task);
			$workflow->incrementTaskCount();

			$om->persist($workflow);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($workflow), PublicationListener::PUBLICATION_CREATED);

			return $this->redirect($this->generateUrl('core_workflow_show', array( 'id' => $workflow->getId(), 'layout' => 'workspace' )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'workflow'     => $workflow,
			'form'         => $form->createView(),
			'owner'        => $owner,
			'tagProposals' => $tagUtils->getProposals($workflow),
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_workflow_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_workflow_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertLockUnlockable($workflow, $lock);

		// Lock or Unlock
		$workflowManager = $this->get(WorkflowManager::class);
		if ($lock) {
			$workflowManager->lock($workflow);
		} else {
			$workflowManager->unlock($workflow);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.workflow.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $workflow->getTitle() )));

		return $this->redirect($this->generateUrl('core_workflow_show', array( 'id' => $workflow->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_workflow_publish")
	 */
	public function publish($id) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertPublishable($workflow);

		// Publish
		$workflowManager = $this->get(WorkflowManager::class);
		$workflowManager->publish($workflow);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.workflow.form.alert.publish_success', array( '%title%' => $workflow->getTitle() )));

		return $this->redirect($this->generateUrl('core_workflow_show', array( 'id' => $workflow->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_workflow_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertUnpublishable($workflow);

		// Unpublish
		$workflowManager = $this->get(WorkflowManager::class);
		$workflowManager->unpublish($workflow);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.workflow.form.alert.unpublish_success', array( '%title%' => $workflow->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_workflow_edit")
	 * @Template("Workflow/Workflow/edit.html.twig")
	 */
	public function edit($id) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertEditabable($workflow);

		$form = $this->createForm(WorkflowType::class, $workflow);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'workflow'     => $workflow,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workflow),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_workflow_update")
	 * @Template("Workflow/Workflow/edit.html.twig")
	 */
	public function update(Request $request, $id) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertEditabable($workflow);

		$previouslyUsedTags = $workflow->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(WorkflowType::class, $workflow);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($workflow);

			if ($workflow->getUser() == $this->getUser()) {
				$workflow->setUpdatedAt(new \DateTime());
			}

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($workflow, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.workflow.form.alert.update_success', array( '%title%' => $workflow->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(WorkflowType::class, $workflow);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'workflow'     => $workflow,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($workflow),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_workflow_delete")
	 */
	public function delete($id) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertDeletable($workflow);

		// Delete
		$workflowManager = $this->get(WorkflowManager::class);
		$workflowManager->delete($workflow);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.workflow.form.alert.delete_success', array( '%title%' => $workflow->getTitle() )));

		return $this->redirect($this->generateUrl('core_workflow_list'));
	}

	/**
	 * @Route("/{id}/chown", requirements={"id" = "\d+"}, name="core_workflow_chown")
	 */
	public function chown(Request $request, $id) {

		$workflow = $this->retrievePublication($id, Workshop::CLASS_NAME);
		$this->assertChownable($workflow);

		$targetUser = $this->retrieveOwner($request);

		// Change owner
		$workflowManager = $this->get(WorkflowManager::class);
		$workflowManager->changeOwner($workflow, $targetUser);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.workflow.form.alert.chown_success', array( '%title%' => $workflow->getTitle() )));

		return $this->redirect($this->generateUrl('core_workflow_show', array( 'id' => $workflow->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_workflow_widget")
	 * @Template("Workflow/Workflow:widget-xhr.html.twig")
	 */
	public function widget($id) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow, true);

		return array(
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_workflow_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workflow_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workflow_howtos_filter_page")
	 * @Template("Workflow/Workflow/howtos.html.twig")
	 */
	public function howtos(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $howtoRepository->findPaginedByWorkflow($workflow, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workflow_howtos_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'howtos'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Howto/Howto/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workflow' => $workflow,
		));
	}

	/**
	 * @Route("/{id}/creations", requirements={"id" = "\d+"}, name="core_workflow_creations")
	 * @Route("/{id}/creations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workflow_creations_filter")
	 * @Route("/{id}/creations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workflow_creations_filter_page")
	 * @Template("Workflow/Workflow/creations.html.twig")
	 */
	public function creations(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		// Creations

		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByWorkflow($workflow, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workflow_creations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'creations'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Creation/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workflow' => $workflow,
		));
	}

	/**
	 * @Route("/{id}/plans", requirements={"id" = "\d+"}, name="core_workflow_plans")
	 * @Route("/{id}/plans/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workflow_plans_filter")
	 * @Route("/{id}/plans/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workflow_plans_filter_page")
	 * @Template("Workflow/Workflow/plans.html.twig")
	 */
	public function plans(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		// Plans

		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedByWorkflow($workflow, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workflow_plans_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'plans'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workflow' => $workflow,
		));
	}

	/**
	 * @Route("/{id}/ateliers", requirements={"id" = "\d+"}, name="core_workflow_workshops")
	 * @Route("/{id}/ateliers/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workflow_workshops_filter")
	 * @Route("/{id}/ateliers/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workflow_workshops_filter_page")
	 * @Template("Workflow/Workflow/workshops.html.twig")
	 */
	public function workshops(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		// Workshops

		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workshopRepository->findPaginedByWorkflow($workflow, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workflow_workshops_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workshops'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Workshop/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workflow' => $workflow,
		));
	}

	/**
	 * @Route("/{id}/inspirations", requirements={"id" = "\d+"}, name="core_workflow_inspirations")
	 * @Route("/{id}/inspirations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workflow_inspirations_filter")
	 * @Route("/{id}/inspirations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workflow_inspirations_filter_page")
	 * @Template("Workflow/Workflow/inspirations.html.twig")
	 */
	public function inspirations(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		// Inspirations

		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workflowRepository->findPaginedByRebound($workflow, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workflow_inspirations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workflows'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Workflow/Workflow/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workflow' => $workflow,
		));
	}

	/**
	 * @Route("/{id}/rebonds", requirements={"id" = "\d+"}, name="core_workflow_rebounds")
	 * @Route("/{id}/rebonds/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_workflow_rebounds_filter")
	 * @Route("/{id}/rebonds/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_workflow_rebounds_filter_page")
	 * @Template("Workflow/Workflow/rebounds.html.twig")
	 */
	public function rebounds(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		// Rebounds

		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workflowRepository->findPaginedByInspiration($workflow, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_workflow_rebounds_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workflows'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Workflow/Workflow/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'workflow' => $workflow,
		));
	}

	/**
	 * @Route("/{id}/restart_confirm", requirements={"id" = "\d+"}, name="core_workflow_restart_confirm")
	 * @Template("Workflow/Workflow/restart-confirm-xhr.html.twig")
	 */
	public function restartConfirm(Request $request, $id) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		return array(
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/{id}/restart", requirements={"id" = "\d+"}, name="core_workflow_restart")
	 */
	public function restart($id) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		// Restart
		$workflowManager = $this->get(WorkflowManager::class);
		$workflowManager->restart($workflow, $this->getUser());

		$this->_push($workflow, array(
			'workflowInfos'    => $this->_generateWorkflowInfos($workflow),
			'updatedTaskInfos' => $this->_generateTaskInfos($workflow->getTasks(), self::TASKINFO_STATUS | self::TASKINFO_BOX),
		));

		return new Response();
	}

	/**
	 * @Route("/{id}/copy", requirements={"id" = "\d+"}, name="core_workflow_copy")
	 */
	public function copy($id) {

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		// Copy
		$workflowManager = $this->get(WorkflowManager::class);
		$newWorkflow = $workflowManager->copy($workflow, $this->getUser());

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.workflow.form.alert.copy_success', array( '%title%' => $workflow->getTitle() )));

		return $this->redirect($this->generateUrl('core_workflow_show', array( 'id' => $newWorkflow->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/statistics", requirements={"id" = "\d+"}, name="core_workflow_statistics")
	 * @Template("Workflow/Workflow/statistics-xhr.html.twig")
	 */
	public function statistics(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$taskRepository = $om->getRepository(Task::CLASS_NAME);

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		$dataDurationsPerLabel = array();
		foreach ($workflow->getLabels() as $label) {
			$tasks = $taskRepository->findByLabel($label);
			$duration = 0;
			foreach ($tasks as $task) {
				$duration += $task->getDuration();
			}
			$dataDurationsPerLabel[] = array(
				'name'     => $label->getName(),
				'color'    => $label->getColor(),
				'duration' => floor($duration / 60),
			);
		}

		return array(
			'workflow'              => $workflow,
			'dataDurationsPerLabel' => $dataDurationsPerLabel,
		);
	}

	/**
	 * @Route("/{id}.html", name="core_workflow_show")
	 * @Template("Workflow/Workflow/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$witnessManager = $this->get(WitnessManager::class);

		$layout = $request->get('layout', 'page');

		$workflow = $this->retrievePublication($id, Workflow::CLASS_NAME);
		$this->assertShowable($workflow);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($workflow), PublicationListener::PUBLICATION_SHOWN);

		$followerUtils = $this->get(FollowerUtils::class);
		$likableUtils = $this->get(LikableUtils::class);

		$parameters = array(
			'workflow'          => $workflow,
			'permissionContext' => $this->getPermissionContext($workflow),
			'readOnly'          => !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser() != $this->getUser(),
			'durationsHidden'   => !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser() != $this->getUser(),
			'followerContext'   => $followerUtils->getFollowerContext($workflow->getUser(), $this->getUser()),
			'likeContext'       => $likableUtils->getLikeContext($workflow, $this->getUser()),
		);

		if ($layout == 'workspace') {

			// TODO switch layout from workspace to page if referrer is not LADB server

			// Exclude bots
			$isBot = preg_match('/bot|spider|crawler|curl|facebookexternalhit|^$/i', $_SERVER['HTTP_USER_AGENT']);

			if (!$isBot) {
				return $this->render('Workflow/Workflow/show-workspace.html.twig', $parameters);
			}

		}

		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);

		$parameters = array_merge($parameters, array(
			'watchContext'      => $watchableUtils->getWatchContext($workflow, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($workflow),
			'collectionContext' => $collectionnableUtils->getCollectionContext($workflow),
		));

		return $parameters;
	}

	/**
	 * @Route("/", name="core_workflow_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_workflow_list_page")
	 * @Template("Workflow/Workflow/list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_workflow_list_page)');
		}

		$layout = $request->get('layout', 'view');

		$routeParameters = array();
		if ($layout != 'view') {
			$routeParameters['layout'] = $layout;
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
				switch ($facet->name) {

					// Filters /////

					case 'admin-all':
						if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {

							$filters[] = new \Elastica\Query\MatchAll();

							$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

							$noGlobalFilters = true;
						}
						break;

					case 'mine':

						if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

							$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							$filters[] = $filter;

							$couldUseDefaultSort = true;

						}

						break;

					case 'period':

						if ($facet->value == 'last7days') {

							$filters[] = new \Elastica\Query\Range('createdAt', array( 'gte' => 'now-7d/d' ));

						} elseif ($facet->value == 'last30days') {

							$filters[] = new \Elastica\Query\Range('createdAt', array( 'gte' => 'now-30d/d' ));

						}

						break;

					case 'tag':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'tags.label' ));
						$filters[] = $filter;

						break;

					case 'author':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'user.displayname', 'user.fullname', 'user.username'  ));
						$filters[] = $filter;

						break;

					case 'license':

						$filter = new \Elastica\Query\Term([ 'license.strippedname' => [ 'value' => $facet->value, 'boost' => 1.0 ] ]);
						$filters[] = $filter;

						break;

					case 'content-creations':

						$filter = new \Elastica\Query\Range('creationCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-plans':

						$filter = new \Elastica\Query\Range('planCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-workshops':

						$filter = new \Elastica\Query\Range('workshopCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-howtos':

						$filter = new \Elastica\Query\Range('howtoCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'changedAt' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-views':
						$sort = array( 'viewCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'likeCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-comments':
						$sort = array( 'commentCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-copies':
						$sort = array( 'copyCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'title^100', 'body', 'tags.label' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) use ($layout) {

				$this->pushGlobalVisibilityFilter($filters, $layout != 'choice', true);

			},
			'workflow_workflow',
			\App\Entity\Workflow\Workflow::CLASS_NAME,
			'core_workflow_list_page',
			$routeParameters
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'workflows'       => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters,
		));

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('Workflow/Workflow/list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('Workflow/Workflow/list-xhr.html.twig', $parameters);
			}
		}

		if ($layout == 'choice') {
			return $this->render('Workflow/Workflow/list-choice.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}/internal/diagram", name="core_workflow_internal_diagram")
	 * @Template("Workflow/Workflow/diagram.html.twig")
	 */
	public function internalDiagram($id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		return array(
			'workflow' => $workflow,
		);
	}

}
