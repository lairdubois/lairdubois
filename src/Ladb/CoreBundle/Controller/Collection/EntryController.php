<?php

namespace Ladb\CoreBundle\Controller\Collection;

use Ladb\CoreBundle\Utils\CollectionnableUtils;
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

			// Update related entity
			$entity->incrementCollectionCount();

			$om->persist($entry);
			$om->flush();

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

		// Update related entity
		$entity->incrementCollectionCount(-1);

		$om->remove($entry);
		$om->flush();

		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);

		return array(
			'entryContext' => $collectionnableUtils->getEntryContext($collection, $entity),
		);
	}

}
