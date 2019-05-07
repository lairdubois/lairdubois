<?php

namespace Ladb\CoreBundle\Controller\Wonder;

use Ladb\CoreBundle\Entity\Core\Resource;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\ResourceUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Entity\Wonder\Workshop;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Form\Type\Wonder\PlanType;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\PlanUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Utils\EmbeddableUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Manager\Wonder\PlanManager;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\StripableUtils;

/**
 * @Route("/plans")
 */
class PlanController extends Controller {

	/**
	 * @Route("/new", name="core_plan_new")
	 * @Template("LadbCoreBundle:Wonder/Plan:new.html.twig")
	 */
	public function newAction() {

		$plan = new Plan();
		$form = $this->createForm(PlanType::class, $plan);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($plan),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_plan_create")
	 * @Template("LadbCoreBundle:Wonder/Plan:new.html.twig")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$plan = new Plan();
		$form = $this->createForm(PlanType::class, $plan);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($plan);

			$plan->setUser($this->getUser());
			$plan->setMainPicture($plan->getPictures()->first());
			$this->getUser()->getMeta()->incrementPrivatePlanCount();

			$planUtils = $this->get(PlanUtils::NAME);
			$planUtils->generateKinds($plan);
			$planUtils->processSketchup3DWarehouseUrl($plan);
			$planUtils->processA360Url($plan);

			$om->persist($plan);
			$om->flush();

			// Create zip archive after inserting plan into database to be sure we have an ID
			$planUtils->createZipArchive($plan);

			$om->flush();	// Resave to store file size

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($plan));

			return $this->redirect($this->generateUrl('core_plan_show', array('id' => $plan->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'plan'         => $plan,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($plan),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_plan_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_plan_unlock")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_plan_lock or core_plan_unlock)")
	 */
	public function lockUnlockAction($id, $lock) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneById($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}
		if ($plan->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_plan_lock or core_plan_unlock)');
		}

		// Lock or Unlock
		$planManager = $this->get(PlanManager::NAME);
		if ($lock) {
			$planManager->lock($plan);
		} else {
			$planManager->unlock($plan);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.plan.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $plan->getTitle() )));

		return $this->redirect($this->generateUrl('core_plan_show', array( 'id' => $plan->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_plan_publish")
	 */
	public function publishAction($id) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $plan->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_plan_publish)');
		}
		if ($plan->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_plan_publish)');
		}
		if ($plan->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked (core_plan_publish)');
		}

		// Publish
		$planManager = $this->get(PlanManager::NAME);
		$planManager->publish($plan);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.plan.form.alert.publish_success', array( '%title%' => $plan->getTitle() )));

		return $this->redirect($this->generateUrl('core_plan_show', array( 'id' => $plan->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_plan_unpublish")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_plan_unpublish)")
	 */
	public function unpublishAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}
		if ($plan->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_plan_unpublish)');
		}

		// Unpublish
		$planManager = $this->get(PlanManager::NAME);
		$planManager->unpublish($plan);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.plan.form.alert.unpublish_success', array( '%title%' => $plan->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_plan_edit")
	 * @Template("LadbCoreBundle:Wonder/Plan:edit.html.twig")
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $plan->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_plan_edit)');
		}

		$form = $this->createForm(PlanType::class, $plan);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'plan'         => $plan,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($plan),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_plan_update")
	 * @Template("LadbCoreBundle:Wonder/Plan:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $plan->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_plan_update)');
		}

		$picturedUtils = $this->get(PicturedUtils::NAME);
		$picturedUtils->resetPictures($plan); // Reset pictures array to consider form pictures order

		$previouslyUsedTags = $plan->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(PlanType::class, $plan);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($plan);

			$embaddableUtils = $this->get(EmbeddableUtils::NAME);
			$embaddableUtils->resetSticker($plan);

			$stripableUtils = $this->get(StripableUtils::NAME);
			$stripableUtils->resetStrip($plan);

			$planUtils = $this->get(PlanUtils::NAME);
			$planUtils->generateKinds($plan);
			$planUtils->processSketchup3DWarehouseUrl($plan);
			$planUtils->processA360Url($plan);
			$planUtils->createZipArchive($plan);

			$plan->setMainPicture($plan->getPictures()->first());
			if ($plan->getUser()->getId() == $this->getUser()->getId()) {
				$plan->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($plan, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.plan.form.alert.update_success', array( '%title%' => $plan->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(PlanType::class, $plan);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'plan'         => $plan,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($plan),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_plan_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$planUtils = $this->get(PlanUtils::NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !($plan->getIsDraft() === true && $plan->getUser()->getId() == $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_plan_delete)');
		}

		// Delete
		$planManager = $this->get(PlanManager::NAME);
		$planManager->delete($plan);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.plan.form.alert.delete_success', array( '%title%' => $plan->getTitle() )));

		if ($plan->getIsDraft()) {
			return $this->redirect($this->generateUrl('core_user_show_plans', array( 'username' => $this->getUser()->getUsernameCanonical() )));
		}
		return $this->redirect($this->generateUrl('core_plan_list'));
	}

	/**
	 * @Route("/{id}/download", requirements={"id" = "\d+"}, name="core_plan_download")
	 */
	public function downloadAction($id) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneById($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}
		if ($plan->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $plan->getUser()->getId() != $this->getUser()->getId())) {
				throw $this->createNotFoundException('Not allowed (core_plan_download)');
			}
		}

		$planUtils = $this->get(PlanUtils::NAME);
		$zipAbsolutePath = $planUtils->getZipAbsolutePath($plan);
		if (!file_exists($zipAbsolutePath)) {
			if (!$planUtils->createZipArchive($plan)) {
				throw $this->createNotFoundException('Zip archive not found (core_plan_download)');
			}
		}

		$plan->incrementDownloadCount(1);

		$om->flush();

		// Update index
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($plan);

		$content = file_get_contents($zipAbsolutePath);

		$response = new Response();
		$response->headers->set('Content-Type', 'mime/type');
		$response->headers->set('Content-Length', filesize($zipAbsolutePath));
		$response->headers->set('Content-Disposition', 'attachment;filename="lairdubois_'.$plan->getUser()->getUsernameCanonical().'_'.$plan->getSlug().'.zip"');
		$response->headers->set('Expires', 0);
		$response->headers->set('Cache-Control', 'no-cache, must-revalidate');
		$response->headers->set('Pragma', 'no-cache');

		$response->setContent($content);

		return $response;
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_plan_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_plan_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_plan_howtos_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Plan:howtos.html.twig")
	 */
	public function howtosAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $howtoRepository->findPaginedByPlan($plan, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_plan_howtos_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'howtos'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Howto/Howto:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'plan' => $plan,
		));
	}

	/**
	 * @Route("/{id}/creations", requirements={"id" = "\d+"}, name="core_plan_creations")
	 * @Route("/{id}/creations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_plan_creations_filter")
	 * @Route("/{id}/creations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_plan_creations_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Plan:creations.html.twig")
	 */
	public function creationsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}

		// Creations

		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByPlan($plan, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_plan_creations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'creations'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'plan' => $plan,
		));
    }

	/**
	 * @Route("/{id}/processus", requirements={"id" = "\d+"}, name="core_plan_workflows")
	 * @Route("/{id}/processus/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_plan_workflows_filter")
	 * @Route("/{id}/processus/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_plan_workflows_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Plan:workflows.html.twig")
	 */
	public function workflowsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}

		// Workflows

		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workflowRepository->findPaginedByPlan($plan, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_plan_workflows_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workflows'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Howto/Howto:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'plan' => $plan,
		));
	}

	/**
	 * @Route("/{id}/ateliers", requirements={"id" = "\d+"}, name="core_plan_workshops")
	 * @Route("/{id}/ateliers/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_plan_workshops_filter")
	 * @Route("/{id}/ateliers/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_plan_workshops_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Plan:workshops.html.twig")
	 */
	public function workshopsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}

		// Workshops

		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workshopRepository->findPaginedByPlan($plan, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_plan_workshops_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workshops'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Workshop:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'plan' => $plan,
		));
    }

	/**
	 * @Route("/{id}/inspirations", requirements={"id" = "\d+"}, name="core_plan_inspirations")
	 * @Route("/{id}/inspirations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_plan_inspirations_filter")
	 * @Route("/{id}/inspirations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_plan_inspirations_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Plan:inspirations.html.twig")
	 */
	public function inspirationsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}

		// Inspirations

		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedByRebound($plan, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_plan_inspirations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'       => $filter,
			'prevPageUrl'  => $pageUrls->prev,
			'nextPageUrl'  => $pageUrls->next,
			'inspirations' => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'plan' => $plan,
		));
	}

	/**
	 * @Route("/{id}/rebonds", requirements={"id" = "\d+"}, name="core_plan_rebounds")
	 * @Route("/{id}/rebonds/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_plan_rebounds_filter")
	 * @Route("/{id}/rebonds/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_plan_rebounds_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Plan:rebounds.html.twig")
	 */
	public function reboundsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$plan = $planRepository->findOneByIdJoinedOnUser($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}

		// Rebounds

		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedByInspiration($plan, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_plan_rebounds_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'rebounds'    => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'plan' => $plan,
		));
	}

	/**
	 * @Route("/{id}/sticker.png", requirements={"id" = "\d+"}, name="core_plan_sticker_bc")
	 */
	public function bcStickerAction(Request $request, $id) {
		return $this->redirect($this->generateUrl('core_plan_sticker', array( 'id' => $id )));
	}

	/**
	 * @Route("/{id}/sticker", requirements={"id" = "\d+"}, name="core_plan_sticker")
	 */
	public function stickerAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$id = intval($id);

		$plan = $planRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}
		if ($plan->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_plan_sticker)');
		}

		$sticker = $plan->getSticker();
		if (is_null($sticker)) {
			$embeddableUtils = $this->get(EmbeddableUtils::NAME);
			$sticker = $embeddableUtils->generateSticker($plan);
			if (!is_null($sticker)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating sticker (core_plan_sticker)');
			}
		}

		if (!is_null($sticker)) {

			$response = $this->get('liip_imagine.controller')->filterAction($request, $sticker->getWebPath(), '598w');
			return $response;

		} else {
			throw $this->createNotFoundException('No sticker');
		}

	}

	/**
	 * @Route("/{id}/strip", requirements={"id" = "\d+"}, name="core_plan_strip")
	 */
	public function stripAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);

		$id = intval($id);

		$plan = $planRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($plan)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if ($plan->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_plan_strip)');
		}

		$strip = $plan->getStrip();
		if (is_null($strip)) {
			$stripableUtils = $this->get(StripableUtils::NAME);
			$strip = $stripableUtils->generateStrip($plan);
			if (!is_null($strip)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating strip (core_plan_strip)');
			}
		}

		if (!is_null($strip)) {

			$response = $this->get('liip_imagine.controller')->filterAction($request, $strip->getWebPath(), '564w');
			return $response;

		} else {
			throw $this->createNotFoundException('No strip');
		}

	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_plan_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_plan_list_filter_page")
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_plan_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_plan_list_page")
	 * @Template("LadbCoreBundle:Wonder/Plan:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_plan_list_page)');
		}

		$layout = $request->get('layout', 'view');

		$routeParameters = array();
		if ($layout != 'view') {
			$routeParameters['layout'] = $layout;
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				switch ($facet->name) {

					// Filters /////

					case 'mine':

						if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

							if ($facet->value == 'draft') {

								$filter = (new \Elastica\Query\BoolQuery())
									->addFilter(new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsername()))
									->addFilter(new \Elastica\Query\Range('visibility', array( 'lt' => HiddableInterface::VISIBILITY_PUBLIC )))
								;

							} else {

								$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							}

							$filters[] = $filter;

							$couldUseDefaultSort = true;

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

						$filter = new \Elastica\Query\MatchPhrase('license.strippedname', $facet->value);
						$filters[] = $filter;

						break;

					case 'kind':

						$resourceUtils = $this->get(ResourceUtils::NAME);
						$kind = $resourceUtils->getKindFromStrippedName($facet->value);
						$filter = new \Elastica\Query\Range('kinds', array( 'gte' => $kind, 'lte' => $kind ));
						$filters[] = $filter;

						break;

					case 'content-creations':

						$filter = new \Elastica\Query\Range('creationCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-workflows':

						$filter = new \Elastica\Query\Range('workflowCount', array( 'gte' => 1 ));
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

					case 'with-inspiration':

						$filter = new \Elastica\Query\Range('inspirationCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'with-rebound':

						$filter = new \Elastica\Query\Range('reboundCount', array( 'gte' => 1 ));
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

					case 'sort-popular-downloads':
						$sort = array( 'downloadCount' => array( 'order' => 'desc' ) );
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
			function(&$filters) use ($layout) {

				$user = $this->getUser();
				$publicVisibilityFilter = new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC ));
				if (!is_null($user) && $layout != 'choice') {

					$filter = new \Elastica\Query\BoolQuery();
					$filter->addShould(
						$publicVisibilityFilter
					);
					$filter->addShould(
						(new \Elastica\Query\BoolQuery())
							->addFilter(new \Elastica\Query\MatchPhrase('user.username', $user->getUsername()))
							->addFilter(new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PRIVATE )))
					);

				} else {
					$filter = $publicVisibilityFilter;
				}
				$filters[] = $filter;


			},
			'fos_elastica.index.ladb.wonder_plan',
			\Ladb\CoreBundle\Entity\Wonder\Plan::CLASS_NAME,
			'core_plan_list_page',
			$routeParameters
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		$parameters = array_merge($searchParameters, array(
			'plans'           => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters
		));

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('LadbCoreBundle:Wonder/Plan:list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('LadbCoreBundle:Wonder/Plan:list-xhr.html.twig', $parameters);
			}
		}

		if ($layout == 'choice') {
			return $this->render('LadbCoreBundle:Wonder/Plan:list-choice.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivatePlanCount() > 0) {

			$draftPath = $this->generateUrl('core_plan_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivatePlanCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->transchoice('wonder.plan.choice.draft_alert', $draftCount, array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_plan_show")
	 * @Template("LadbCoreBundle:Wonder/Plan:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$plan = $planRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($plan)) {
			if ($response = $witnessManager->checkResponse(Plan::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Plan entity (id='.$id.').');
		}
		if ($plan->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $plan->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Plan::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_plan_show)');
			}
		}

		$embaddableUtils = $this->get(EmbeddableUtils::NAME);
		$referral = $embaddableUtils->processReferer($plan, $request);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($plan));

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userPlans = $explorableUtils->getPreviousAndNextPublishedUserExplorables($plan, $planRepository, $plan->getUser()->getMeta()->getPublicPlanCount());
		$similarPlans = $explorableUtils->getSimilarExplorables($plan, 'fos_elastica.index.ladb.wonder_plan', Plan::CLASS_NAME, $userPlans);

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'plan'              => $plan,
			'userPlans'         => $userPlans,
			'similarPlans'      => $similarPlans,
			'likeContext'       => $likableUtils->getLikeContext($plan, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($plan, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($plan),
			'collectionContext' => $collectionnableUtils->getCollectionContext($plan),
			'followerContext'   => $followerUtils->getFollowerContext($plan->getUser(), $this->getUser()),
			'referral'          => $referral,
		);
	}

}