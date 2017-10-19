<?php

namespace Ladb\CoreBundle\Controller\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Entity\Core\Watch;

/**
 * @Route("/watches")
 */
class WatchController extends Controller {

	/**
	 * @Route("/{entityType}/{entityId}/create", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_watch_create")
	 * @Template("LadbCoreBundle:Core/Watch:create-xhr.html.twig")
	 */
	public function createAction(Request $request, $entityType, $entityId) {

		// Retrieve related entity

		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		// Create the watch
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$watchableUtils->createWatch($entity, $this->getUser());

		// Flashbag
		if (!$this->getUser()->getEmailConfirmed() && $this->getUser()->getNewWatchActivityEmailNotificationEnabled()) {
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

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::NAME);
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

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_watch_delete")
	 * @Template("LadbCoreBundle:Core/Watch:delete-xhr.html.twig")
	 */
	public function deleteAction(Request $request, $id) {
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

		$watchableUtils = $this->get(WatchableUtils::NAME);

		return array(
			'watchContext' => $watchableUtils->getWatchContext($entity, $this->getUser()),
		);
	}

}