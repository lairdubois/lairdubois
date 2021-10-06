<?php

namespace App\Controller\Collection;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Model\HiddableInterface;
use App\Utils\CollectionnableUtils;
use App\Entity\Collection\Entry;

/**
 * @Route("/collections")
 */
class EntryController extends AbstractCollectionBasedController {

	/////

	/**
	 * @Route("/{id}/entry/{entityType}/{entityId}/create", requirements={"id" = "\d+", "entityType" = "\d+", "entityId" = "\d+"}, name="core_collection_entry_create")
	 * @Template("Collection/Entry:create-xhr.html.twig")
	 */
	public function create($id, $entityType, $entityId) {

		$this->createLock('core_collection_entry_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		// Retrieve collection
		$collection = $this->_retrieveCollection($id);

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $collection->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_collection_entry_create)');
		}

		// Retrieve related entity
		$entity = $this->_retrieveRelatedEntity($entityType, $entityId);

		// Create entry
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
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
	 * @Template("Collection/Entry:delete-xhr.html.twig")
	 */
	public function delete($id, $entityType, $entityId) {
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
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
		$collectionnableUtils->deleteEntry($entry, $entity, $om, true);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($collection), PublicationListener::PUBLICATION_CHANGED);

		$collectionnableUtils = $this->get(CollectionnableUtils::class);

		return array(
			'entryContext' => $collectionnableUtils->getEntryContext($collection, $entity),
		);
	}

}
