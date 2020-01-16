<?php

namespace Ladb\CoreBundle\Controller\Collection;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Entity\Collection\Entry;

/**
 * @Route("/collections")
 */
class EntryController extends AbstractCollectionBasedController {

	/////

	/**
	 * @Route("/{id}/entry/{entityType}/{entityId}/create", requirements={"id" = "\d+", "entityType" = "\d+", "entityId" = "\d+"}, name="core_collection_entry_create")
	 * @Template("LadbCoreBundle:Collection/Entry:create-xhr.html.twig")
	 */
	public function createAction($id, $entityType, $entityId) {

		$this->createLock('core_collection_entry_create');

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_entry_create)');
		}

		// Retrieve related entity
		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		// Create entry
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
		try {
			$collectionnableUtils->createEntry($entity, $collection);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}

		return array(
			'entryContext' => $collectionnableUtils->getEntryContext($collection, $entity),
		);
	}

	/**
	 * @Route("/{id}/entry/{entityType}/{entityId}/delete", requirements={"id" = "\d+", "entityType" = "\d+", "entityId" = "\d+"}, name="core_collection_entry_delete")
	 * @Template("LadbCoreBundle:Collection/Entry:delete-xhr.html.twig")
	 */
	public function deleteAction($id, $entityType, $entityId) {
		$om = $this->getDoctrine()->getManager();
		$entryRepository = $om->getRepository(Entry::CLASS_NAME);

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_entry_create)');
		}

		$entry = $entryRepository->findOneByEntityTypeAndEntityIdAndCollection($entityType, $entityId, $collection);
		if (is_null($entry)) {
			throw $this->createNotFoundException('Unable to find Collection Entry entity (collectionId='.$id.', entityType='.$entityType.', entityId='.$entityId.').');
		}

		// Retrieve related entity
		$entity = $this->_retrieveRelatedEntity($entry->getEntityType(), $entry->getEntityId());

		// Delete entry
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
		$collectionnableUtils->deleteEntry($entry, $entity, $om, true);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($collection));

		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);

		return array(
			'entryContext' => $collectionnableUtils->getEntryContext($collection, $entity),
		);
	}

}
