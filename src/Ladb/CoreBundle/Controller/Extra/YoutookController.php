<?php

namespace Ladb\CoreBundle\Controller\Extra;

use Ladb\CoreBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Youtook\Took;
use Ladb\CoreBundle\Form\Type\Youtook\NewTookType;
use Ladb\CoreBundle\Form\Type\Youtook\EditTookType;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\Youtook\TookManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;

/**
 * @Route("/yt")
 */
class YoutookController extends AbstractController {

	/**
	 * @Route("/create", methods={"POST"}, name="core_youtook_create")
	 * @Template("LadbCoreBundle:Extra/Youtook:create-xhr.html.twig")
	 */
	public function createAction(Request $request) {

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

				$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
				$fieldPreprocessorUtils->preprocessFields($took);

				$took->setUser($this->getUser());

				$om->persist($took);
				$om->flush();

			}

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($took));

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
	 * @Template("LadbCoreBundle:Extra/Youtook:edit.html.twig")
	 */
	public function editAction($id) {
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
	 * @Template("LadbCoreBundle:Extra/Youtook:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
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

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($took);

			if ($took->getUser()->getId() == $this->getUser()->getId()) {
				$took->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($took));

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
	public function deleteAction($id) {
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
		$tookManager = $this->get(TookManager::NAME);
		$tookManager->delete($took);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('youtook.form.alert.delete_success', array( '%title%' => $took->getTitle() )));

		return $this->redirect($this->generateUrl('core_youtook_user_list'));
	}

	/**
	 * @Route("/", name="core_youtook_list")
	 * @Route("/{filter}", requirements={"filter" = "\w+"}, name="core_youtook_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_youtook_list_filter_page")
	 * @Template("LadbCoreBundle:Extra/Youtook:list.html.twig")
	 */
	public function listAction(Request $request, $filter = 'all', $page = 0) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$tookRepository = $om->getRepository(Took::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Extra/Youtook:list-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

	/**
	 * @Route("/mes-tooks", name="core_youtook_user_list")
	 * @Route("/mes-tooks/{filter}", requirements={"filter" = "\w+"}, name="core_youtook_user_list_filter")
	 * @Route("/mes-tooks/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_youtook_user_list_filter_page")
	 * @Template("LadbCoreBundle:Extra/Youtook:userList.html.twig")
	 */
	public function userListAction(Request $request, $filter = 'all', $page = 0) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$tookRepository = $om->getRepository(Took::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Extra/Youtook:list-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_youtook_show")
	 * @Template("LadbCoreBundle:Extra/Youtook:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$tookRepository = $om->getRepository(Took::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

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
