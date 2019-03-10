<?php

namespace Ladb\CoreBundle\Controller\Collection;

use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_entry_create)');
		}

		// Retrieve related entity
		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		$om = $this->getDoctrine()->getManager();
		$entryRepository = $om->getRepository(Entry::CLASS_NAME);

		if (!$entryRepository->existsByEntityTypeAndEntityIdAndCollection($entityType, $entityId, $collection)) {

			// Create the new Entry
			$entry = new Entry();
			$entry->setEntityType($entityType);
			$entry->setEntityId($entityId);

			// Add to collection
			$collection->addEntry($entry);
			$collection->incrementEntryCount();
			$collection->incrementEntryTypeCounters($entry->getEntityType());
			$collection->setChangedAt(new \DateTime());
			$collection->setUpdatedAt(new \DateTime());

			// Update related entity
			if ($collection->getIsPublic()) {
				$entity->incrementPublicCollectionCount();
			} else {
				$entity->incrementPrivateCollectionCount();
			}

			$om->persist($entry);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($collection));

		}

		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);

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
			throw $this->createNotFoundException('Unable to find Collection Entry entity (id='.$id.').');
		}

		// Retrieve related entity
		$entity = $this->_retrieveRelatedEntity($entry->getEntityType(), $entry->getEntityId());

		// Remove from collection
		$collection->removeEntry($entry);
		$collection->incrementEntryCount(-1);
		$collection->incrementEntryTypeCounters($entry->getEntityType(), -1);
		$collection->setChangedAt(new \DateTime());
		$collection->setUpdatedAt(new \DateTime());

		// Update related entity
		if ($collection->getIsPublic()) {
			$entity->incrementPublicCollectionCount(-1);
		} else {
			$entity->incrementPrivateCollectionCount(-1);
		}

		$om->remove($entry);
		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($collection));

		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);

		return array(
			'entryContext' => $collectionnableUtils->getEntryContext($collection, $entity),
		);
	}

}
