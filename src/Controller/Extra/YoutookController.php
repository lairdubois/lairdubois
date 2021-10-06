<?php

namespace App\Controller\Extra;

use App\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Entity\Youtook\Took;
use App\Form\Type\Youtook\NewTookType;
use App\Form\Type\Youtook\EditTookType;
use App\Utils\PaginatorUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Manager\Youtook\TookManager;
use App\Manager\Core\WitnessManager;

/**
 * @Route("/yt")
 */
class YoutookController extends AbstractController {

	/**
	 * @Route("/create", methods={"POST"}, name="core_youtook_create")
	 * @Template("Extra/Youtook:create-xhr.html.twig")
	 */
	public function create(Request $request) {

		$this->createLock('core_youtook_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$took = new Took();
		$form = $this->createForm(NewTookType::class, $took);
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Check if took exists
			$tookRepository = $om->getRepository(Took::CLASS_NAME);
			if ($tookRepository->existsByEmbedIdentifierAndUser($took->getEmbedIdentifier(), $this->getUser())) {

				$took = $tookRepository->findOneByEmbedIdentifierAndUser($took->getEmbedIdentifier(), $this->getUser());
				$took->setChangedAt(new \DateTime());

				$om->flush();

			} else {

				$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
				$fieldPreprocessorUtils->preprocessFields($took);

				$took->setUser($this->getUser());

				$om->persist($took);
				$om->flush();

			}

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($took), PublicationListener::PUBLICATION_CREATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('youtook.form.alert.create_success'));

			$success = true;
		}

		return array(
			'success' => isset($success) ? $success : false,
			'took'    => $took,
			'form'    => !isset($success) ? $form->createView() : null,
		);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_youtook_edit")
	 * @Template("Extra/Youtook:edit.html.twig")
	 */
	public function edit($id) {
		$om = $this->getDoctrine()->getManager();
		$tookRepository = $om->getRepository(Took::CLASS_NAME);

		$took = $tookRepository->findOneById($id);
		if (is_null($took)) {
			throw $this->createNotFoundException('Unable to find Video entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $took->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_youtook_edit)');
		}

		$form = $this->createForm(EditTookType::class, $took);

		return array(
			'took' => $took,
			'form'  => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_youtook_update")
	 * @Template("Extra/Youtook:edit.html.twig")
	 */
	public function update(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$tookRepository = $om->getRepository(Took::CLASS_NAME);

		$took = $tookRepository->findOneById($id);
		if (is_null($took)) {
			throw $this->createNotFoundException('Unable to find Video entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $took->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_youtook_update)');
		}

		$form = $this->createForm(EditTookType::class, $took);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($took);

			if ($took->getUser()->getId() == $this->getUser()->getId()) {
				$took->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($took), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('youtook.form.alert.update_success', array( '%title%' => $took->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(EditTookType::class, $took);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		return array(
			'took' => $took,
			'form'  => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_youtook_delete")
	 */
	public function delete($id) {
		$om = $this->getDoctrine()->getManager();
		$tookRepository = $om->getRepository(Took::CLASS_NAME);

		$took = $tookRepository->findOneById($id);
		if (is_null($took)) {
			throw $this->createNotFoundException('Unable to find Took entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $took->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_youtook_delete)');
		}

		// Delete
		$tookManager = $this->get(TookManager::class);
		$tookManager->delete($took);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('youtook.form.alert.delete_success', array( '%title%' => $took->getTitle() )));

		return $this->redirect($this->generateUrl('core_youtook_user_list'));
	}

	/**
	 * @Route("/", name="core_youtook_list")
	 * @Route("/{filter}", requirements={"filter" = "\w+"}, name="core_youtook_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_youtook_list_filter_page")
	 * @Template("Extra/Youtook:list.html.twig")
	 */
	public function list(Request $request, $filter = 'all', $page = 0) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$tookRepository = $om->getRepository(Took::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $tookRepository->findPagined($offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_youtook_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count(), 20, 20);

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'tooks'       => $paginator,
			'tookCount' => $paginator->count(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Extra/Youtook:list-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

	/**
	 * @Route("/mes-tooks", name="core_youtook_user_list")
	 * @Route("/mes-tooks/{filter}", requirements={"filter" = "\w+"}, name="core_youtook_user_list_filter")
	 * @Route("/mes-tooks/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_youtook_user_list_filter_page")
	 * @Template("Extra/Youtook:userList.html.twig")
	 */
	public function userList(Request $request, $filter = 'all', $page = 0) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$tookRepository = $om->getRepository(Took::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $tookRepository->findPaginedByUser($this->getUser(), $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_youtook_user_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count(), 20, 20);

		$took = new Took();
		$form = $this->createForm(NewTookType::class, $took);

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'tooks'       => $paginator,
			'tookCount'   => $paginator->count(),
			'form'        => $form->createView(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Extra/Youtook:list-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_youtook_show")
	 * @Template("Extra/Youtook:show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$tookRepository = $om->getRepository(Took::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$took = $tookRepository->findOneById($id);
		if (is_null($took)) {
			if ($response = $witnessManager->checkResponse(Took::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Took entity (id='.$id.').');
		}

		$referer = $request->headers->get('referer');
		$userAgent = $request->headers->get('User-Agent');

		$isFacebookBotUserAgent = preg_match('/facebookexternalhit/', $userAgent);
		$isLadbReferrer = preg_match('/lairdubois.fr/', $referer);

		if ($isFacebookBotUserAgent || $isLadbReferrer) {
			return array(
				'took' => $took,
			);
		}

		return $this->redirect($took->getUrl());
	}

}
