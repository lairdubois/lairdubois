<?php

namespace Ladb\CoreBundle\Controller\Core;

use Ladb\CoreBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\Join;
use Ladb\CoreBundle\Model\PublicationInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\JoinableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\JoinableUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\TypableUtils;

/**
 * @Route("/joins")
 */
class JoinController extends AbstractController {

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::NAME);
		try {
			$entity = $typableUtils->findTypable($entityType, $entityId);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}
		if (!($entity instanceof JoinableInterface)) {
			throw $this->createNotFoundException('Entity must implements JoinableInterface.');
		}
		return $entity;
	}

	/////

	/**
	 * @Route("/{entityType}/{entityId}/create", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_join_create")
	 * @Template("LadbCoreBundle:Core/Join:create-xhr.html.twig")
	 */
	public function createAction(Request $request, $entityType, $entityId) {

		$this->createLock('core_join_create');

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);
		if (!($entity instanceof JoinableInterface)) {
			throw $this->createNotFoundException('Entity need to implements JoinableInterface.');
		} else if (!$entity->getIsJoinable()) {
			throw $this->createNotFoundException('Entity could not be joined.');
		}
		if ($entity instanceof HiddableInterface && !$entity->getIsPublic()) {
			throw $this->createNotFoundException('Hidden entity could not be joined.');
		}

		$om = $this->getDoctrine()->getManager();
		$joinRepository = $om->getRepository(Join::CLASS_NAME);

		if (!$joinRepository->existsByEntityTypeAndEntityIdAndUser($entityType, $entityId, $this->getUser())) {

			$entity->incrementJoinCount();

			// Prepare join

			$join = new Join();
			$join->setEntityType($entityType);
			$join->setEntityId($entityId);
			$join->setUser($this->getUser());

			$om->persist($join);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createJoinActivity($join, false);

			if ($entity instanceof PublicationInterface) {

				// Set ChangedAt to now
				$entity->setChangedAt(new \DateTime());

				// Dispatch publication event
				$dispatcher = $this->get('event_dispatcher');
				$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($entity));

			}

			// Auto watch
			if ($entity instanceof WatchableInterface) {
				$watchableUtils = $this->get(WatchableUtils::NAME);
				$watchableUtils->autoCreateWatch($entity, $this->getUser());
			}

			$om->flush();

		}

		if (!$request->isXmlHttpRequest()) {

			// Return to
			$returnToUrl = $request->get('rtu');
			if (is_null($returnToUrl)) {
				$returnToUrl = $request->headers->get('referer');
			}

			return $this->redirect($returnToUrl);
		}

		$joinableUtils = $this->get(JoinableUtils::NAME);

		return array(
			'joinContext' => $joinableUtils->getJoinContext($entity, $this->getUser()),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_join_delete")
	 * @Template("LadbCoreBundle:Core/Join:delete-xhr.html.twig")
	 */
	public function deleteAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$joinRepository = $om->getRepository(Join::CLASS_NAME);

		$join = $joinRepository->findOneById($id);
		if (is_null($join)) {
			throw $this->createNotFoundException('Unable to find Join entity (id='.$id.').');
		}
		if ($join->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_join_delete)');
		}

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($join->getEntityType(), $join->getEntityId());
		if ($entity instanceof HiddableInterface && !$entity->getIsPublic()) {
			throw $this->createNotFoundException('Hidden entity could not be unjoind.');
		}

		// Update related entity
		$entity->incrementJoinCount(-1);

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->deleteActivitiesByJoin($join, false);

		// Delete join
		$om->remove($join);
		$om->flush();

		// Update index
		if ($entity instanceof IndexableInterface) {
			$searchUtils = $this->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($entity);
		}

		if (!$request->isXmlHttpRequest()) {

			// Return to (use referer because the user is already logged)
			$returnToUrl = $request->headers->get('referer');

			return $this->redirect($returnToUrl);
		}

		$joinableUtils = $this->get(JoinableUtils::NAME);

		return array(
			'joinContext' => $joinableUtils->getJoinContext($entity, $this->getUser()),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_join_list_byentity")
	 * @Route("/{entityType}/{entityId}/{page}", requirements={"entityType" = "\d+", "entityId" = "\d+", "page" = "\d+"}, name="core_join_list_byentity_page")
	 * @Template("LadbCoreBundle:Core/Join:list-byentity.html.twig")
	 */
	public function listByEntityAction(Request $request, $entityType, $entityId, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$joinRepository = $om->getRepository(Join::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		// Retrive joins

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $joinRepository->findPaginedByEntityTypeAndEntityIdJoinedOnUser($entityType, $entityId, $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_join_list_byentity_page', array( 'entityType' => $entityType, 'entityId' => $entityId ), $page, $paginator->count());

		$parameters = array(
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'entity'      => $entity,
			'joins'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Core/Join:list-byentity-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

}