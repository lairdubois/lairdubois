<?php

namespace App\Controller\Wonder;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Controller\AbstractController;
use App\Controller\CustomOwnerControllerTrait;
use App\Controller\PublicationControllerTrait;
use App\Entity\Core\Member;
use App\Entity\Core\Tip;
use App\Entity\Event\Event;
use App\Entity\Offer\Offer;
use App\Utils\ElasticaQueryUtils;
use App\Utils\FeedbackableUtils;
use App\Utils\MaybeUtils;
use App\Entity\Knowledge\School;
use App\Entity\Qa\Question;
use App\Utils\CollectionnableUtils;
use App\Manager\Wonder\CreationManager;
use App\Manager\Core\WitnessManager;
use App\Entity\Workflow\Workflow;
use App\Model\HiddableInterface;
use App\Utils\StripableUtils;
use App\Form\Type\Wonder\CreationType;
use App\Utils\PaginatorUtils;
use App\Utils\LikableUtils;
use App\Utils\WatchableUtils;
use App\Utils\CommentableUtils;
use App\Utils\FollowerUtils;
use App\Utils\ExplorableUtils;
use App\Utils\TagUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\SearchUtils;
use App\Utils\BlockBodiedUtils;
use App\Utils\PicturedUtils;
use App\Utils\EmbeddableUtils;
use App\Utils\TypableUtils;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Entity\Blog\Post;
use App\Entity\Howto\Howto;
use App\Entity\Knowledge\Provider;
use App\Entity\Core\Spotlight;
use App\Entity\Wonder\Plan;
use App\Entity\Wonder\Creation;
use App\Entity\Core\View;

/**
 * @Route("/creations")
 */
class CreationController extends AbstractController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_creation_new")
	 * @Template("Wonder/Creation/new.html.twig")
	 */
	public function new(Request $request) {

		$creation = new Creation();
		$creation->addBodyBlock(new \App\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(CreationType::class, $creation);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'owner'        => $this->retrieveOwner($request),
			'tagProposals' => $tagUtils->getProposals($creation),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_creation_create")
	 * @Template("Wonder/Creation/new.html.twig")
	 */
	public function create(Request $request) {

		$owner = $this->retrieveOwner($request);

		$this->createLock('core_creation_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$creation = new Creation();
		$form = $this->createForm(CreationType::class, $creation);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($creation);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($creation);

			$creation->setUser($owner);
			$creation->setMainPicture($creation->getPictures()->first());
			$owner->getMeta()->incrementPrivateCreationCount();

			$om->persist($creation);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($creation), PublicationListener::PUBLICATION_CREATED);

			return $this->redirect($this->generateUrl('core_creation_show', array( 'id' => $creation->getSluggedId() )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'creation'     => $creation,
			'form'         => $form->createView(),
			'owner'        => $owner,
			'tagProposals' => $tagUtils->getProposals($creation),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_creation_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_creation_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertLockUnlockable($creation, $lock);

		// Lock or Unlock
		$creationManager = $this->get(CreationManager::class);
		if ($lock) {
			$creationManager->lock($creation);
		} else {
			$creationManager->unlock($creation);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_creation_show', array( 'id' => $creation->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_creation_publish")
	 */
	public function publish($id) {

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertPublishable($creation);

		// Publish
		$creationManager = $this->get(CreationManager::class);
		$creationManager->publish($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.publish_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_creation_show', array( 'id' => $creation->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_creation_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertUnpublishable($creation);

		// Unpublish
		$creationManager = $this->get(CreationManager::class);
		$creationManager->unpublish($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.unpublish_success', array( '%title%' => $creation->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_creation_edit")
	 * @Template("Wonder/Creation/edit.html.twig")
	 */
	public function edit($id) {

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertEditabable($creation);

		$form = $this->createForm(CreationType::class, $creation);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'creation'     => $creation,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($creation),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_creation_update")
	 * @Template("Wonder/Creation/edit.html.twig")
	 */
	public function update(Request $request, $id) {

		$doUp = $request->get('ladb_do_up', false) && $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertEditabable($creation);

		$originalBodyBlocks = $creation->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $creation->getTags()->toArray();	// Need to be an array to copy values

		$picturedUtils = $this->get(PicturedUtils::class);
		$picturedUtils->resetPictures($creation); // Reset pictures array to consider form pictures order

		$creation->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(CreationType::class, $creation);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($creation, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($creation);

			$embeddableUtils = $this->get(EmbeddableUtils::class);
			$embeddableUtils->resetSticker($creation);

			$stripableUtils = $this->get(StripableUtils::class);
			$stripableUtils->resetStrip($creation);

			$creation->setMainPicture($creation->getPictures()->first());
			if ($doUp) {
				$creation->setChangedAt(new \DateTime());
			}
			if ($creation->getUser() == $this->getUser()) {
				$creation->setUpdatedAt(new \DateTime());
			}

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			if ($doUp) {
				$dispatcher->dispatch(new PublicationEvent($creation), PublicationListener::PUBLICATION_CHANGED);
			}
			$dispatcher->dispatch(new PublicationEvent($creation, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.update_success', array( '%title%' => $creation->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(CreationType::class, $creation);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'creation'     => $creation,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($creation),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_creation_delete")
	 */
	public function delete($id) {

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertDeletable($creation);

		// Delete
		$creationManager = $this->get(CreationManager::class);
		$creationManager->delete($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.delete_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_creation_list'));
	}

	/**
	 * @Route("/{id}/chown", requirements={"id" = "\d+"}, name="core_creation_chown")
	 */
	public function chown(Request $request, $id) {

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertChownable($creation);

		$targetUser = $this->retrieveOwner($request);

		// Change owner
		$creationManager = $this->get(CreationManager::class);
		$creationManager->changeOwner($creation, $targetUser);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.chown_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_creation_show', array( 'id' => $creation->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/questions", requirements={"id" = "\d+"}, name="core_creation_questions")
	 * @Route("/{id}/questions/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_questions_filter")
	 * @Route("/{id}/questions/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_questions_filter_page")
	 * @Template("Wonder/Creation/questions.html.twig")
	 */
	public function questions(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation);

		// Questions

		$questionRepository = $om->getRepository(Question::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $questionRepository->findPaginedByCreation($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_questions_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'questions'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Qa/Question/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/plans", requirements={"id" = "\d+"}, name="core_creation_plans")
	 * @Route("/{id}/plans/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_plans_filter")
	 * @Route("/{id}/plans/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_plans_filter_page")
	 * @Template("Wonder/Creation/plans.html.twig")
	 */
	public function plans(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation);

		// Plans

		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedByCreation($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_plans_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'plans'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Plan/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/projets", requirements={"id" = "\d+"}, name="core_creation_projects")
	 */
	public function projects($id) {
		return $this->redirect($this->generateUrl('core_creation_howtos', array( 'id' => $id )));
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_creation_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_howtos_filter_page")
	 * @Template("Wonder/Creation/howtos.html.twig")
	 */
	public function howtos(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation);

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $howtoRepository->findPaginedByCreation($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_howtos_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'howtos'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Howto/Howto/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/processus", requirements={"id" = "\d+"}, name="core_creation_workflows")
	 * @Route("/{id}/processus/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_workflows_filter")
	 * @Route("/{id}/processus/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_workflows_filter_page")
	 * @Template("Wonder/Creation/workflows.html.twig")
	 */
	public function workflows(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation);

		// Workflows

		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workflowRepository->findPaginedByCreation($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_workflows_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

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
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/fournisseurs", requirements={"id" = "\d+"}, name="core_creation_providers")
	 * @Route("/{id}/fournisseurs/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_providers_filter")
	 * @Route("/{id}/fournisseurs/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_providers_filter_page")
	 * @Template("Wonder/Creation/providers.html.twig")
	 */
	public function providers(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation);

		// Providers

		$providerRepository = $om->getRepository(Provider::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $providerRepository->findPaginedByCreation($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_providers_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'providers'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Knowledge/Provider/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/ecoles", requirements={"id" = "\d+"}, name="core_creation_schools")
	 * @Route("/{id}/ecoles/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_schools_filter")
	 * @Route("/{id}/ecoles/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_schools_filter_page")
	 * @Template("Wonder/Creation/schools.html.twig")
	 */
	public function schools(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation);

		// Schools

		$schoolRepository = $om->getRepository(School::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $schoolRepository->findPaginedByCreation($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_schools_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'schools'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Knowledge/School/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/inspirations", requirements={"id" = "\d+"}, name="core_creation_inspirations")
	 * @Route("/{id}/inspirations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_inspirations_filter")
	 * @Route("/{id}/inspirations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_inspirations_filter_page")
	 * @Template("Wonder/Creation/inspirations.html.twig")
	 */
	public function inspirations(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation);

		// Inspirations

		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByRebound($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_inspirations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

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
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/rebonds", requirements={"id" = "\d+"}, name="core_creation_rebounds")
	 * @Route("/{id}/rebonds/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_rebounds_filter")
	 * @Route("/{id}/rebonds/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_rebounds_filter_page")
	 * @Template("Wonder/Creation/rebounds.html.twig")
	 */
	public function rebounds(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation);

		// Rebounds

		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByInspiration($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_rebounds_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

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
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/sticker.png", requirements={"id" = "\d+"}, name="core_creation_sticker_bc")
	 */
	public function bcSticker(Request $request, $id) {
		return $this->redirect($this->generateUrl('core_creation_sticker', array( 'id' => $id )));
	}

	/**
	 * @Route("/{id}/sticker", requirements={"id" = "\d+"}, name="core_creation_sticker")
	 */
	public function sticker(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation, true);

		$sticker = $creation->getSticker();
		if (is_null($sticker)) {
			$embeddableUtils = $this->get(EmbeddableUtils::class);
			$sticker = $embeddableUtils->generateSticker($creation);
			if (!is_null($sticker)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating sticker (core_creation_sticker)');
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
	 * @Route("/{id}/strip", requirements={"id" = "\d+"}, name="core_creation_strip")
	 */
	public function strip(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation, true);

		$strip = $creation->getStrip();
		if (is_null($strip)) {
			$stripableUtils = $this->get(StripableUtils::class);
			$strip = $stripableUtils->generateStrip($creation);
			if (!is_null($strip)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating strip (core_creation_strip)');
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
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_creation_widget")
	 * @Template("Wonder/Creation/widget-xhr.html.twig")
	 */
	public function widget($id) {

		$creation = $this->retrievePublication($id, Creation::CLASS_NAME);
		$this->assertShowable($creation, true);

		return array(
			'creation' => $creation,
		);
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_creation_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_list_filter_page")
	 */
	public function goneList(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_creation_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_creation_list_page")
	 * @Template("Wonder/Creation/list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_creation_list_page)');
		}

		$layout = $request->get('layout', 'view');
		$homepage = $request->get('homepage', false);

		$routeParameters = array();
		if ($layout != 'view') {
			$routeParameters['layout'] = $layout;
		}

		/////

		if ($page == 0 && $layout == 'view') {
			$om = $this->getDoctrine()->getManager();

			if ($homepage) {

				// Spotlight
				$spotlightRepository = $om->getRepository(Spotlight::CLASS_NAME);
				$spotlight = $spotlightRepository->findOneLast();

				if (!is_null($spotlight) && $page == 0) {
					$typableUtils = $this->get(TypableUtils::class);
					try {
						$spotlightEntity = $typableUtils->findTypable($spotlight->getEntityType(), $spotlight->getEntityId());
					} catch (\Exception $e) {
						throw $this->createNotFoundException($e->getMessage());
					}
				} else {
					$spotlightEntity = null;
				}

				if (!is_null($spotlightEntity) && $spotlightEntity instanceof Creation) {
					$excludedIds = array( $spotlightEntity->getId() );
				}

				// Collection highlight
				//$collectionRepository = $om->getRepository(Collection::CLASS_NAME);
				$highlightedCollection = null; //$collectionRepository->findOneByIdAndUser(2465, $this->getUser());

				// RunningEvents
				if (is_null($highlightedCollection)) {
					$eventRepository = $om->getRepository(Event::CLASS_NAME);
					$runningEvents = $eventRepository->findByRunningNowByUser($this->getUser());
				}

			}

			// Tip & Offer highlight
			if ((!isset($highlightedCollection) || is_null($highlightedCollection)) && (!isset($runningEvents) || empty($runningEvents))) {

				$maybeUtils = $this->get(MaybeUtils::class);
				if ($maybeUtils->canDoIt(0, 10, 'tip')) {
					$tipRepository = $om->getRepository(Tip::CLASS_NAME);
					$highlightedTip = $tipRepository->findOneRandomByUser($this->getUser());
				} else if ($maybeUtils->canDoIt(0, 5, 'offer')) {
					$offerRepository = $om->getRepository(Offer::CLASS_NAME);
					$highlightedOffer = $offerRepository->findOneRandomByCategoryAndUser(Offer::CATEGORY_JOB, $this->getUser());
				}

			}

			// PostHighlight
			$postRepository = $om->getRepository(Post::CLASS_NAME);
			$highlightedPost = $postRepository->findOneLastOnHighlightLevel($this->get('security.authorization_checker')->isGranted('ROLE_USER') ? Post::HIGHLIGHT_LEVEL_USER_ONLY : Post::HIGHLIGHT_LEVEL_ALL);

			// Check if post is already viewed
			if (!is_null($highlightedPost) && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
				$viewRepository = $om->getRepository(View::CLASS_NAME);
				if ($viewRepository->existsByEntityTypeAndEntityIdAndUserAndKind($highlightedPost->getType(), $highlightedPost->getId(), $this->getUser(), View::KIND_SHOWN)) {
					$highlightedPost = null;
				}
			}

		}

		/////

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
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

					case 'woods':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::class);
						$filters[] = $elasticaQueryUtils->createShouldMatchPhraseQuery('woods.label', $facet->value);

						break;

					case 'tools':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'tools.label' ));
						$filters[] = $filter;

						break;

					case 'finishes':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'finishes.label' ));
						$filters[] = $filter;

						break;

					case 'hardwares':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'hardwares.label' ));
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

					case 'content-questions':

						$filter = new \Elastica\Query\Range('questionCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-plans':

						$filter = new \Elastica\Query\Range('planCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-howtos':

						$filter = new \Elastica\Query\Range('howtoCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-workflows':

						$filter = new \Elastica\Query\Range('workflowCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-providers':

						$filter = new \Elastica\Query\Range('providerCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-schools':

						$filter = new \Elastica\Query\Range('schoolCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-videos':

						$filter = new \Elastica\Query\Range('bodyBlockVideoCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'with-feedback':

						$filter = new \Elastica\Query\Range('feedbackCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'with-inspiration':

						$filter = new \Elastica\Query\Range('inspirationCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'with-rebound':

						$filter = new \Elastica\Query\Range('reboundCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'with-spotlight':

						$filter = new \Elastica\Query\Range('withEnabledSpotlight', array( 'gt' => 0 ));
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
			function(&$filters, &$sort) use ($homepage) {

				$sort = array('changedAt' => array('order' => 'desc'));

			},
			function(&$filters) use ($layout) {

				$this->pushGlobalVisibilityFilter($filters, $layout != 'choice', true);

			},
			'wonder_creation',
			\App\Entity\Wonder\Creation::CLASS_NAME,
			'core_creation_list_page',
			$routeParameters,
			isset($excludedIds) ? $excludedIds : null
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'creations'       => $searchParameters['entities'],
			'layout'          => $layout,
			'homepage'        => $homepage,
			'routeParameters' => $routeParameters,
		));

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('Wonder/Creation/list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('Wonder/Creation/list-xhr.html.twig', $parameters);
			}
		}

		if ($layout == 'choice') {
			return $this->render('Wonder/Creation/list-choice.html.twig', $parameters);
		}

		$parameters = array_merge($parameters, array(
			'spotlight'             => isset($spotlight) ? $spotlight : null,
			'spotlightEntity'       => isset($spotlightEntity) ? $spotlightEntity : null,
			'highlightedPost'       => isset($highlightedPost) ? $highlightedPost : null,
			'runningEvents'         => isset($runningEvents) ? $runningEvents : null,
			'highlightedCollection' => isset($highlightedCollection) ? $highlightedCollection : null,
			'highlightedTip'        => isset($highlightedTip) ? $highlightedTip : null,
			'highlightedOffer'      => isset($highlightedOffer) ? $highlightedOffer : null,
		));

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateCreationCount() > 0) {

			$draftPath = $this->generateUrl('core_creation_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateCreationCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->trans('wonder.creation.choice.draft_alert', array( 'count' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/feed.xml", name="core_creation_feed")
	 */
	public function feed() {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$translator = $this->get('translator');

		$feed = new \Suin\RSSWriter\Feed();

		$channel = new \Suin\RSSWriter\Channel();
		$channel
			->title('L\'Air du Bois : '.$translator->trans('wonder.creation.list'))
			->description($translator->trans('wonder.creation.description'))
			->url($this->generateUrl('core_creation_list', array(), \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL))
			->feedUrl($this->generateUrl('core_creation_feed', array(), \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL))
			->language('fr-FR')
			->pubDate((new \DateTime())->getTimestamp())
			->lastBuildDate((new \DateTime())->getTimestamp())
			->ttl(60)
			->appendTo($feed);

		$creations = $creationRepository->findPagined(0, 15);
		foreach ($creations as $creation) {

			$item = new \Suin\RSSWriter\Item();
			$item
				->title($creation->getTitle())
				->description($creation->getBodyExtract().'<br><a href="'.$this->generateUrl('core_creation_show', array('id' => $creation->getSluggedId()), \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL).'">Lire la suite...</a>')
				->url($this->generateUrl('core_creation_show', array('id' => $creation->getSluggedId()), \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL))
				->author($creation->getUser()->getDisplayName())
				->pubDate($creation->getChangedAt()->getTimestamp())
				->guid($this->generateUrl('core_creation_show', array('id' => $creation->getId()), \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL), true)
				->enclosure($this->get('liip_imagine.cache.manager')->getBrowserPath($creation->getMainPicture()->getWebPath(), '594x294o'), 0, image_type_to_mime_type(exif_imagetype($creation->getMainPicture()->getAbsoluteMasterPath())))
			;

			foreach ($creation->getTags() as $tag) {
				$item->category($tag->getLabel());
			}

			$item->appendTo($channel);

		}

		return new Response(
			$feed->render(),
			Response::HTTP_OK,
			array( 'content-type' => 'application/rss+xml' )
		);
	}

	/**
	 * @Route("/{id}.html", name="core_creation_show")
	 * @Template("Wonder/Creation/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			if ($response = $witnessManager->checkResponse(Creation::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		$this->assertShowable($creation);

		$embaddableUtils = $this->get(EmbeddableUtils::class);
		$referral = $embaddableUtils->processReferer($creation, $request);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($creation), PublicationListener::PUBLICATION_SHOWN);

		$explorableUtils = $this->get(ExplorableUtils::class);
		$userCreations = $explorableUtils->getPreviousAndNextPublishedUserExplorables($creation, $creationRepository, $creation->getUser()->getMeta()->getPublicCreationCount());
		$similarCreations = $explorableUtils->getSimilarExplorables($creation, 'wonder_creation', Creation::CLASS_NAME, $userCreations);

		$woodsLabels = array();
		foreach ($creation->getWoods() as $wood) {
			$woodsLabels[] = $wood->getLabel();
		}
		$woodsString = implode(',', $woodsLabels);

		$searchUtils = $this->get(SearchUtils::class);
		$elasticaQueryUtils = $this->get(ElasticaQueryUtils::class);
		$searchableWoodCount = $searchUtils->searchEntitiesCount(array( $elasticaQueryUtils->createShouldMatchPhraseQuery('name', $woodsString) ), 'knowledge_wood');

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$feedbackableUtils = $this->get(FeedbackableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
		$followerUtils = $this->get(FollowerUtils::class);

		return array(
			'creation'            => $creation,
			'permissionContext'   => $this->getPermissionContext($creation),
			'searchableWoodQuery' => $woodsString,
			'searchableWoodCount' => $searchableWoodCount,
			'userCreations'       => $userCreations,
			'similarCreations'    => $similarCreations,
			'likeContext'         => $likableUtils->getLikeContext($creation, $this->getUser()),
			'watchContext'        => $watchableUtils->getWatchContext($creation, $this->getUser()),
			'feedbackContext'     => $feedbackableUtils->getFeedbackContext($creation),
			'commentContext'      => $commentableUtils->getCommentContext($creation),
			'collectionContext'   => $collectionnableUtils->getCollectionContext($creation),
			'followerContext'     => $followerUtils->getFollowerContext($creation->getUser(), $this->getUser()),
			'referral'            => $referral,
		);
	}

	/**
	 * @Route("/{id}/admin/converttoworkshop", requirements={"id" = "\d+"}, name="core_creation_admin_converttoworkshop")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_creation_admin_converttoworkshop)")
	 */
	public function adminConvertToWorkshop($id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Convert
		$creationManager = $this->get(CreationManager::class);
		$workshop = $creationManager->convertToWorkshop($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.admin.alert.converttoworkshop_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_workshop_show', array( 'id' => $workshop->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/admin/converttohowto", requirements={"id" = "\d+"}, name="core_creation_admin_converttohowto")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_creation_admin_converttohowto)")
	 */
	public function adminConvertToHowto($id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Convert
		$creationManager = $this->get(CreationManager::class);
		$howto = $creationManager->convertToHowto($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.admin.alert.converttohowto_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/admin/converttofind", requirements={"id" = "\d+"}, name="core_creation_admin_converttofind")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_creation_admin_converttofind)")
	 */
	public function adminConvertToFind($id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Convert
		$creationManager = $this->get(CreationManager::class);
		$find = $creationManager->convertToFind($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.admin.alert.converttofind_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_find_show', array( 'id' => $find->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/admin/converttoquestion", requirements={"id" = "\d+"}, name="core_creation_admin_converttoquestion")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_creation_admin_converttoquestion)")
	 */
	public function adminConvertToQuestion($id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Convert
		$creationManager = $this->get(CreationManager::class);
		$question = $creationManager->convertToQuestion($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.admin.alert.converttoquestion_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_qa_question_show', array( 'id' => $question->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/admin/converttooffer", requirements={"id" = "\d+"}, name="core_creation_admin_converttooffer")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_creation_admin_converttooffer)")
	 */
	public function adminConvertToOffer($id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Convert
		$creationManager = $this->get(CreationManager::class);
		$offer = $creationManager->convertToOffer($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.admin.alert.converttooffer_success', array( '%title%' => $offer->getTitle() )));

		return $this->redirect($this->generateUrl('core_offer_show', array( 'id' => $offer->getSluggedId() )));
	}

}
