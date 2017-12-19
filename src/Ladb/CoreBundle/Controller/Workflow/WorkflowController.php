<?php

namespace Ladb\CoreBundle\Controller\Workflow;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Form\Type\Workflow\WorkflowType;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Entity\Workflow\Task;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Manager\Workflow\WorkflowManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;

/**
 * @Route("/processus")
 */
class WorkflowController extends AbstractWorkflowBasedController {

	/////

	/**
	 * @Route("/new", name="core_workflow_new")
	 * @Template("LadbCoreBundle:Workflow:new.html.twig")
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
			$workflow->incrementTaskCount();

			$om->persist($workflow);
			$om->flush();

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->insertEntityToIndex($workflow);

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($workflow));

			return $this->redirect($this->generateUrl('core_workflow_show', array( 'id' => $workflow->getSluggedId(), 'layout' => 'workspace' )));
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
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_qa_question_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_qa_question_unlock")
	 */
	public function lockUnlockAction($id, $lock) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_qa_question_lock or core_qa_question_unlock)');
		}
		if ($workflow->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_qa_question_lock or core_qa_question_unlock)');
		}

		// Lock or Unlock
		$workflowManager = $this->get(WorkflowManager::NAME);
		if ($lock) {
			$workflowManager->lock($workflow);
		} else {
			$workflowManager->unlock($workflow);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.workflow.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $question->getTitle() )));

		return $this->redirect($this->generateUrl('core_workflow_show', array( 'id' => $workflow->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_workflow_publish")
	 */
	public function publishAction($id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_workflow_publish)');
		}
		if ($workflow->getVisibility() === AbstractPublication::VISIBILITY_PUBLIC) {
			throw $this->createNotFoundException('Already published (core_workflow_publish)');
		}
		if ($workflow->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked (core_workflow_publish)');
		}

		// Publish
		$workflowManager = $this->get(WorkflowManager::NAME);
		$workflowManager->publish($workflow);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('workflow.workflow.form.alert.publish_success', array( '%title%' => $workflow->getTitle() )));

		return $this->redirect($this->generateUrl('core_workflow_show', array( 'id' => $workflow->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_workflow_unpublish")
	 */
	public function unpublishAction(Request $request, $id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_workflow_unpublish)');
		}
		if ($workflow->getVisibility() === AbstractPublication::VISIBILITY_PUBLIC) {
			throw $this->createNotFoundException('Already private (core_workflow_publish)');
		}

		// Unpublish
		$workflowManager = $this->get(WorkflowManager::NAME);
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
	 * @Template("LadbCoreBundle:Workflow:edit.html.twig")
	 */
	public function editAction($id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

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

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

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

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

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
	 * @Route("/{id}/diagram", name="core_workflow_diagram")
	 * @Template("LadbCoreBundle:Workflow:diagram.html.twig")
	 */
	public function diagramAction(Request $request, $id) {

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		return array(
			'workflow' => $workflow,
		);
	}

	/**
	 * @Route("/{id}.html", name="core_workflow_show")
	 * @Template("LadbCoreBundle:Workflow:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$witnessManager = $this->get(WitnessManager::NAME);

		$layout = $request->get('layout', 'page');

		// Retrieve workflow
		$workflow = $this->_retrieveWorkflow($id);

		if ($workflow->getVisibility() === AbstractPublication::VISIBILITY_PRIVATE) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $workflow->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Workflow::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_workflow_show)');
			}
		}

		// TODO switch layout from workspace to page if referrer is not LADB server

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($workflow));

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		$parameters = array(
			'workflow'        => $workflow,
			'readOnly'        => !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser() != $this->getUser(),
			'likeContext'     => $likableUtils->getLikeContext($workflow, $this->getUser()),
			'watchContext'    => $watchableUtils->getWatchContext($workflow, $this->getUser()),
			'commentContext'  => $commentableUtils->getCommentContext($workflow),
			'followerContext' => $followerUtils->getFollowerContext($workflow->getUser(), $this->getUser()),
		);

		if ($layout == 'workspace') {
			return $this->render('LadbCoreBundle:Workflow:show-workspace.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/", name="core_workflow_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_workflow_list_page")
	 * @Template("LadbCoreBundle:Workflow:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$layout = $request->get('layout', 'view');

		$routeParameters = array();
		if ($layout != 'view') {
			$routeParameters['layout'] = $layout;
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				switch ($facet->name) {

					// Filters /////

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

					case 'mine':

						$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
						$filters[] = $filter;

						$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

						break;

					case 'license':

						$filter = new \Elastica\Query\MatchPhrase('license.strippedname', $facet->value);
						$filters[] = $filter;

						break;

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'changedAt' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-views':
						$sort = array( 'viewCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'likeCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-comments':
						$sort = array( 'commentCount' => array( 'order' => 'desc' ) );
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

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) {

				$user = $this->getUser();
				$publicVisibilityFilter = new \Elastica\Query\Range('visibility', array( 'gte' => AbstractPublication::VISIBILITY_PUBLIC ));
				if (!is_null($user)) {

					$filter = new \Elastica\Query\BoolQuery();
					$filter->addShould(
						$publicVisibilityFilter
					);
					$filter->addShould(
						(new \Elastica\Query\BoolQuery())
							->addMust(new \Elastica\Query\MatchPhrase('user.username', $user->getUsername()))
							->addMust(new \Elastica\Query\Range('visibility', array( 'gte' => AbstractPublication::VISIBILITY_PRIVATE )))
					);

				} else {
					$filter = $publicVisibilityFilter;
				}
				$filters[] = $filter;


			},
			'fos_elastica.index.ladb.workflow_workflow',
			\Ladb\CoreBundle\Entity\Workflow\Workflow::CLASS_NAME,
			'core_workflow_list_page',
			$routeParameters
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities']));

		$parameters = array_merge($searchParameters, array(
			'workflows'          => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters,
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Workflow:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}/statistics", requirements={"id" = "\d+"}, name="core_workflow_statistics")
	 * @Template("LadbCoreBundle:Workflow:statistics-xhr.html.twig")
	 */
	public function statisticsAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$taskRepository = $om->getRepository(Task::CLASS_NAME);

		// Retrieve Workflow
		$workflow = $this->_retrieveWorkflow($id);

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

}
