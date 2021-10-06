<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Utils\WatchableUtils;
use App\Utils\TypableUtils;
use App\Utils\PaginatorUtils;
use App\Model\WatchableInterface;
use App\Entity\Core\Watch;

/**
 * @Route("/watches")
 */
class WatchController extends AbstractController {

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::class);
		try {
			$entity = $typableUtils->findTypable($entityType, $entityId);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}
		if (!($entity instanceof WatchableInterface)) {
			throw $this->createNotFoundException('Entity must implements WatchableInterface.');
		}
		return $entity;
	}

	/////

	/**
	 * @Route("/{entityType}/{entityId}/create", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_watch_create")
	 * @Template("Core/Watch:create-xhr.html.twig")
	 */
	public function create(Request $request, $entityType, $entityId) {

		$this->createLock('core_watch_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		// Create the watch
		$watchableUtils = $this->get(WatchableUtils::class);
		$watchableUtils->createWatch($entity, $this->getUser());

		// Flashbag
		if (!$this->getUser()->getEmailConfirmed() && $this->getUser()->getMeta()->getNewWatchActivityEmailNotificationEnabled()) {
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.alert.email_not_confirmed_error'));
		}

		if (!$request->isXmlHttpRequest()) {

			// Return to
			$returnToUrl = $request->get('rtu');
			if (is_null($returnToUrl)) {
				$returnToUrl = $request->headers->get('referer');
			}

			return $this->redirect($returnToUrl);
		}

		return array(
			'watchContext' => $watchableUtils->getWatchContext($entity, $this->getUser()),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_watch_delete")
	 * @Template("Core/Watch:delete-xhr.html.twig")
	 */
	public function delete(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$watchRepository = $om->getRepository(Watch::CLASS_NAME);

		$watch = $watchRepository->findOneById($id);
		if (is_null($watch)) {
			throw $this->createNotFoundException('Unable to find Watch entity (id='.$id.').');
		}
		if ($watch->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_watch_delete)');
		}

		// Retrieve related entity
		$entity = $this->_retrieveRelatedEntity($watch->getEntityType(), $watch->getEntityId());

		// Update related entity

		$entity->incrementWatchCount(-1);

		// Delete watch
		$om->remove($watch);
		$om->flush();

		if (!$request->isXmlHttpRequest()) {

			// Return to (use referer because the user is already logged)
			$returnToUrl = $request->headers->get('referer');

			return $this->redirect($returnToUrl);
		}

		$watchableUtils = $this->get(WatchableUtils::class);

		return array(
			'watchContext' => $watchableUtils->getWatchContext($entity, $this->getUser()),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_watch_list_byentity")
	 * @Route("/{entityType}/{entityId}/{page}", requirements={"entityType" = "\d+", "entityId" = "\d+", "page" = "\d+"}, name="core_watch_list_byentity_page")
	 * @Template("Core/Watch:list-byentity.html.twig")
	 */
	public function listByEntity(Request $request, $entityType, $entityId, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$watchRepository = $om->getRepository(Watch::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		// Retrive watchs

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $watchRepository->findPaginedByEntityTypeAndEntityIdJoinedOnUser($entityType, $entityId, $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_watch_list_byentity_page', array( 'entityType' => $entityType, 'entityId' => $entityId ), $page, $paginator->count());

		$parameters = array(
			'prevPageUrl'  => $pageUrls->prev,
			'nextPageUrl'  => $pageUrls->next,
			'entity'       => $entity,
			'watchs'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Watch:list-byentity-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

}