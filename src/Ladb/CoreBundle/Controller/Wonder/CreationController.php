<?php

namespace Ladb\CoreBundle\Controller\Wonder;

use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\StripableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Manager\Wonder\CreationManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Form\Type\Wonder\CreationType;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Utils\EmbeddableUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Entity\Blog\Post;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Knowledge\Provider;
use Ladb\CoreBundle\Entity\Core\Spotlight;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Entity\Core\View;

/**
 * @Route("/creations")
 */
class CreationController extends Controller {

	/**
	 * @Route("/new", name="core_creation_new")
	 * @Template("LadbCoreBundle:Wonder/Creation:new.html.twig")
	 */
	public function newAction() {

		$creation = new Creation();
		$creation->addBodyBlock(new \Ladb\CoreBundle\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(CreationType::class, $creation);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($creation),
		);
	}

	/**
	 * @Route("/create", name="core_creation_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Wonder/Creation:new.html.twig")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$creation = new Creation();
		$form = $this->createForm(CreationType::class, $creation);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($creation);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($creation);

			$creation->setUser($this->getUser());
			$creation->setMainPicture($creation->getPictures()->first());
			$this->getUser()->getMeta()->incrementPrivateCreationCount();

			$om->persist($creation);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($creation));

			return $this->redirect($this->generateUrl('core_creation_show', array( 'id' => $creation->getSluggedId() )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'creation'     => $creation,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($creation),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_creation_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_creation_unlock")
	 */
	public function lockUnlockAction($id, $lock) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_creation_lock or core_creation_unlock)');
		}
		if ($creation->getIsLocked() === $lock) {
			throw $this->createNotFoundException('Already '.($lock ? '' : 'un').'locked (core_creation_lock or core_creation_unlock)');
		}

		// Lock or Unlock
		$creationManager = $this->get(CreationManager::NAME);
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
	public function publishAction($id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneByIdJoinedOnUser($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $creation->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_creation_publish)');
		}
		if ($creation->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_creation_publish)');
		}
		if ($creation->getIsLocked() === true) {
			throw $this->createNotFoundException('Locked (core_creation_publish)');
		}

		// Publish
		$creationManager = $this->get(CreationManager::NAME);
		$creationManager->publish($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.publish_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_creation_show', array( 'id' => $creation->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_creation_unpublish")
	 */
	public function unpublishAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneByIdJoinedOnUser($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_creation_unpublish)');
		}
		if ($creation->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_creation_unpublish)');
		}

		// Unpublish
		$creationManager = $this->get(CreationManager::NAME);
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
	 * @Template("LadbCoreBundle:Wonder/Creation:edit.html.twig")
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $creation->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_creation_edit)');
		}

		$form = $this->createForm(CreationType::class, $creation);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'creation'     => $creation,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($creation),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, name="core_creation_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Wonder/Creation:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneByIdJoinedOnUser($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $creation->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_creation_update)');
		}

		$originalBodyBlocks = $creation->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $creation->getTags()->toArray();	// Need to be an array to copy values

		$picturedUtils = $this->get(PicturedUtils::NAME);
		$picturedUtils->resetPictures($creation); // Reset pictures array to consider form pictures order

		$creation->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(CreationType::class, $creation);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($creation, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($creation);

			$embeddableUtils = $this->get(EmbeddableUtils::NAME);
			$embeddableUtils->resetSticker($creation);

			$stripableUtils = $this->get(StripableUtils::NAME);
			$stripableUtils->resetStrip($creation);

			$creation->setMainPicture($creation->getPictures()->first());
			if ($creation->getUser()->getId() == $this->getUser()->getId()) {
				$creation->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($creation, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.update_success', array( '%title%' => $creation->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(CreationType::class, $creation);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'creation'     => $creation,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($creation),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_creation_delete")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !($creation->getIsDraft() === true && $creation->getUser()->getId() == $this->getUser()->getId())) {
			throw $this->createNotFoundException('Not allowed (core_creation_delete)');
		}

		// Delete
		$creationManager = $this->get(CreationManager::NAME);
		$creationManager->delete($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.delete_success', array( '%title%' => $creation->getTitle() )));

		if ($creation->getIsDraft()) {
			return $this->redirect($this->generateUrl('core_user_show_creations', array( 'username' => $this->getUser()->getUsernameCanonical() )));
		}
		return $this->redirect($this->generateUrl('core_creation_list'));
	}

	/**
	 * @Route("/{id}/plans", requirements={"id" = "\d+"}, name="core_creation_plans")
	 * @Route("/{id}/plans/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_plans_filter")
	 * @Route("/{id}/plans/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_plans_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Creation:plans.html.twig")
	 */
	public function plansAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Plans

		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/projets", requirements={"id" = "\d+"}, name="core_creation_projects")
	 */
	public function projectsAction($id) {
		return $this->redirect($this->generateUrl('core_creation_howtos', array( 'id' => $id )));
	}

	/**
	 * @Route("/{id}/pas-a-pas", requirements={"id" = "\d+"}, name="core_creation_howtos")
	 * @Route("/{id}/pas-a-pas/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_howtos_filter")
	 * @Route("/{id}/pas-a-pas/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_howtos_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Creation:howtos.html.twig")
	 */
	public function howtosAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Howtos

		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Howto/Howto:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/fournisseurs", requirements={"id" = "\d+"}, name="core_creation_providers")
	 * @Route("/{id}/fournisseurs/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_providers_filter")
	 * @Route("/{id}/fournisseurs/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_providers_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Creation:providers.html.twig")
	 */
	public function providersAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Providers

		$providerRepository = $om->getRepository(Provider::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Howto/Howto:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/inspirations", requirements={"id" = "\d+"}, name="core_creation_inspirations")
	 * @Route("/{id}/inspirations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_inspirations_filter")
	 * @Route("/{id}/inspirations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_inspirations_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Creation:inspiration.html.twig")
	 */
	public function inspirationsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Inspirations

		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByRebound($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_inspirations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'       => $filter,
			'prevPageUrl'  => $pageUrls->prev,
			'nextPageUrl'  => $pageUrls->next,
			'inspirations' => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/rebonds", requirements={"id" = "\d+"}, name="core_creation_rebounds")
	 * @Route("/{id}/rebonds/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_creation_rebounds_filter")
	 * @Route("/{id}/rebonds/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_rebounds_filter_page")
	 * @Template("LadbCoreBundle:Wonder/Creation:rebounds.html.twig")
	 */
	public function reboundsAction(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Rebounds

		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByInspiration($creation, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_creation_rebounds_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'rebounds'    => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'creation' => $creation,
		));
	}

	/**
	 * @Route("/{id}/sticker.png", requirements={"id" = "\d+"}, name="core_creation_sticker_png")
	 * @Route("/{id}/sticker", requirements={"id" = "\d+"}, name="core_creation_sticker")
	 */
	public function stickerAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$id = intval($id);

		$creation = $creationRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if ($creation->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_creation_sticker)');
		}

		$sticker = $creation->getSticker();
		if (is_null($sticker)) {
			$embeddableUtils = $this->get(EmbeddableUtils::NAME);
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
	public function stripAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$id = intval($id);

		$creation = $creationRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if ($creation->getIsDraft() === true) {
			throw $this->createNotFoundException('Not allowed (core_creation_strip)');
		}

		$strip = $creation->getStrip();
		if (is_null($strip)) {
			$stripableUtils = $this->get(StripableUtils::NAME);
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
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_creation_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_creation_list_filter_page")
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_creation_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_creation_list_page")
	 * @Template("LadbCoreBundle:Wonder/Creation:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$searchUtils = $this->get(SearchUtils::NAME);

		$layout = $request->get('layout', 'view');
		$homepage = $request->get('homepage', false);

		$routeParameters = array();
		if ($layout != 'view') {
			$routeParameters['layout'] = $layout;
		}

		/////

		if ($page == 0) {

			if ($homepage) {

				// Spotlight
				$spotlightRepository = $om->getRepository(Spotlight::CLASS_NAME);
				$spotlight = $spotlightRepository->findOneLast();

				if (!is_null($spotlight) && $page == 0) {
					$typableUtils = $this->get(TypableUtils::NAME);
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
			function($facet, &$filters, &$sort) {
				switch ($facet->name) {

					// Filters /////

					case 'mine':

						if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

							if ($facet->value == 'draft') {

								$filter = (new \Elastica\Query\BoolQuery())
									->addMust(new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsername()))
									->addMust(new \Elastica\Query\Range('visibility', array( 'lt' => HiddableInterface::VISIBILITY_PUBLIC )))
								;

							} else {

								$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							}

							$filters[] = $filter;

							$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

						}

						break;

					case 'tag':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'tags.label' ));
						$filters[] = $filter;

						break;

					case 'woods':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'woods.label' ));
						$filters[] = $filter;

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

					case 'author':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'user.displayname', 'user.fullname', 'user.username'  ));
						$filters[] = $filter;

						break;

					case 'license':

						$filter = new \Elastica\Query\MatchPhrase('license.strippedname', $facet->value);
						$filters[] = $filter;

						break;

					case 'content-plans':

						$filter = new \Elastica\Query\Range('planCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-howtos':

						$filter = new \Elastica\Query\Range('howtoCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-providers':

						$filter = new \Elastica\Query\Range('providerCount', array( 'gte' => 1 ));
						$filters[] = $filter;

						break;

					case 'content-videos':

						$filter = new \Elastica\Query\Range('bodyBlockVideoCount', array( 'gte' => 1 ));
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
				$publicVisibilityFilter = new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC ));
				if (!is_null($user)) {

					$filter = new \Elastica\Query\BoolQuery();
					$filter->addShould(
						$publicVisibilityFilter
					);
					$filter->addShould(
						(new \Elastica\Query\BoolQuery())
							->addMust(new \Elastica\Query\MatchPhrase('user.username', $user->getUsername()))
							->addMust(new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PRIVATE )))
					);

				} else {
					$filter = $publicVisibilityFilter;
				}
				$filters[] = $filter;


			},
			'fos_elastica.index.ladb.wonder_creation',
			\Ladb\CoreBundle\Entity\Wonder\Creation::CLASS_NAME,
			'core_creation_list_page',
			$routeParameters,
			isset($excludedIds) ? $excludedIds : null
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities']));

		$parameters = array_merge($searchParameters, array(
			'creations'       => $searchParameters['entities'],
			'layout'          => $layout,
			'homepage'        => $homepage,
			'routeParameters' => $routeParameters,
		));

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('LadbCoreBundle:Wonder/Creation:list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
			}
		}

		$parameters = array_merge($parameters, array(
			'spotlight'       => isset($spotlight) ? $spotlight : null,
			'spotlightEntity' => isset($spotlightEntity) ? $spotlightEntity : null,
			'highlightedPost' => isset($highlightedPost) ? $highlightedPost : null,
		));

		if ($layout == 'choice') {
			return $this->render('LadbCoreBundle:Wonder/Creation:list-choice.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateCreationCount() > 0) {

			$draftPath = $this->generateUrl('core_creation_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateCreationCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->transchoice('wonder.creation.choice.draft_alert', $draftCount, array( '%count%' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_creation_show")
	 * @Template("LadbCoreBundle:Wonder/Creation:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$creation = $creationRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($creation)) {
			if ($response = $witnessManager->checkResponse(Creation::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}
		if ($creation->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $creation->getUser()->getId() != $this->getUser()->getId())) {
				if ($response = $witnessManager->checkResponse(Creation::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_creation_show)');
			}
		}

		$embaddableUtils = $this->get(EmbeddableUtils::NAME);
		$referral = $embaddableUtils->processReferer($creation, $request);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($creation));

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userCreations = $explorableUtils->getPreviousAndNextPublishedUserExplorables($creation, $creationRepository, $creation->getUser()->getMeta()->getPublicCreationCount());
		$similarCreations = $explorableUtils->getSimilarExplorables($creation, 'fos_elastica.index.ladb.wonder_creation', Creation::CLASS_NAME, $userCreations);

		$woodsLabels = array();
		foreach ($creation->getWoods() as $wood) {
			$woodsLabels[] = $wood->getLabel();
		}
		$woodsString = implode(',', $woodsLabels);

		$searchUtils = $this->get(SearchUtils::NAME);
		$searchableWoodCount = $searchUtils->searchEntitiesCount(array( new \Elastica\Query\Match('name', $woodsString) ), 'fos_elastica.index.ladb.knowledge_wood');

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'creation'            => $creation,
			'searchableWoodQuery' => $woodsString,
			'searchableWoodCount' => $searchableWoodCount,
			'userCreations'       => $userCreations,
			'similarCreations'    => $similarCreations,
			'likeContext'         => $likableUtils->getLikeContext($creation, $this->getUser()),
			'watchContext'        => $watchableUtils->getWatchContext($creation, $this->getUser()),
			'commentContext'      => $commentableUtils->getCommentContext($creation),
			'followerContext'     => $followerUtils->getFollowerContext($creation->getUser(), $this->getUser()),
			'referral'			  => $referral,
		);
	}

	/**
	 * @Route("/{id}/admin/converttoworkshop", requirements={"id" = "\d+"}, name="core_creation_admin_converttoworkshop")
	 */
	public function adminConvertToWorkshopAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Convert
		$creationManager = $this->get(CreationManager::NAME);
		$workshop = $creationManager->convertToWorkshop($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.admin.alert.converttoworkshop_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_workshop_show', array( 'id' => $workshop->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/admin/converttohowto", requirements={"id" = "\d+"}, name="core_creation_admin_converttohowto")
	 */
	public function adminConvertToHowtoAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneById($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Workshop entity (id='.$id.').');
		}

		// Convert
		$creationManager = $this->get(CreationManager::NAME);
		$howto = $creationManager->convertToHowto($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.admin.alert.converttohowto_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/admin/converttofind", requirements={"id" = "\d+"}, name="core_creation_admin_converttofind")
	 */
	public function adminConvertToFindAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);

		$creation = $creationRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($creation)) {
			throw $this->createNotFoundException('Unable to find Creation entity (id='.$id.').');
		}

		// Convert
		$creationManager = $this->get(CreationManager::NAME);
		$find = $creationManager->convertToFind($creation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.admin.alert.converttofind_success', array( '%title%' => $creation->getTitle() )));

		return $this->redirect($this->generateUrl('core_find_show', array( 'id' => $find->getSluggedId() )));
	}

}
