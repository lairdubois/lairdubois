<?php

namespace App\Utils;

use App\Entity\Collection\Collection;
use App\Entity\Collection\Entry;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Manager\Collection\CollectionManager;
use App\Manager\Core\PictureManager;
use App\Model\CollectionnableInterface;
use App\Model\HiddableInterface;
use App\Model\PicturedInterface;

class CollectionnableUtils extends AbstractContainerAwareUtils {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.PictureManager::class,
        ));
    }

	public function createEntry(CollectionnableInterface $collectionnable, Collection $collection) {
		$om = $this->getDoctrine()->getManager();
		$pictureManager = $this->get(PictureManager::class);

		if ($collectionnable instanceof HiddableInterface && !$collectionnable->getIsPublic()) {
			throw new \Exception('Entity must be public (entityType='.$collectionnable->getType().', entityId='.$collectionnable->getId().')');
		}
		if ($collectionnable === $collection) {
			throw new \Exception('Entity can not be the collection itself');
		}

		// Create the new Entry
		$entry = new Entry();
		$entry->setEntityType($collectionnable->getType());
		$entry->setEntityId($collectionnable->getId());

		// Add to collection
		$collection->addEntry($entry);
		$collection->incrementEntryCount();
		$collection->incrementEntryTypeCounters($entry->getEntityType());
		$collection->setChangedAt(new \DateTime());
		$collection->setUpdatedAt(new \DateTime());
		if (is_null($collection->getMainPicture()) && $collectionnable instanceof PicturedInterface && !is_null($collectionnable->getMainPicture())) {

			// Duplicate the entyt mainPicture
			$mainPicture = $pictureManager->duplicate($collectionnable->getMainPicture());
			$mainPicture->setUser($collection->getUser());
			$collection->setMainPicture($mainPicture);

		}

		// Update related entity
		if ($collection->getIsPublic()) {
			$collectionnable->incrementPublicCollectionCount();
		} else {
			$collectionnable->incrementPrivateCollectionCount();
		}

		$om->persist($entry);
		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($collection), PublicationListener::PUBLICATION_CHANGED);

	}

	public function deleteEntries(CollectionnableInterface $collectionnable, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$entryRepository = $om->getRepository(Entry::CLASS_NAME);

		$entries = $entryRepository->findByEntityTypeAndEntityId($collectionnable->getType(), $collectionnable->getId());
		foreach ($entries as $entry) {
			$this->deleteEntry($entry, $collectionnable, $om, false);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function deleteEntry(Entry $entry, CollectionnableInterface $collectionnable, $om, $flush = false) {
		$collection = $entry->getCollection();

		// Remove from collection
		$collection->removeEntry($entry);
		$collection->incrementEntryCount(-1);
		$collection->incrementEntryTypeCounters($entry->getEntityType(), -1);
		$collection->setChangedAt(new \DateTime());
		$collection->setUpdatedAt(new \DateTime());

		// Update collectionnable collection count
		if ($collection->getIsPublic()) {
			$collectionnable->incrementPublicCollectionCount(-1);
		} else {
			$collectionnable->incrementPrivateCollectionCount(-1);
		}

		// Reove entry from DB
		$om->remove($entry);

		if ($flush) {
			$om->flush();
		}
	}

	/////

	public function getCollectionContext(CollectionnableInterface $collectionnable) {
		return array(
			'entityType'        => $collectionnable->getType(),
			'entityId'          => $collectionnable->getId(),
			'isCollectionnable' => $collectionnable instanceof HiddableInterface ? $collectionnable->getIsPublic() : true,
		);
	}

	public function getEntryContext(Collection $collection, CollectionnableInterface $collectionnable) {
		$om = $this->getDoctrine()->getManager();
		$entryRepository = $om->getRepository(Entry::CLASS_NAME);

		return array(
			'collection'      => $collection,
			'entityType'      => $collectionnable->getType(),
			'entityId'        => $collectionnable->getId(),
			'isCollectionned' => $entryRepository->existsByEntityTypeAndEntityIdAndCollection($collectionnable->getType(), $collectionnable->getId(), $collection),
		);
	}

	/////

	public function transferCollections(CollectionnableInterface $collectionnableSrc, CollectionnableInterface $collectionnableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$entryRepository = $om->getRepository(Entry::CLASS_NAME);

		// Retrieve entries
		$entries = $entryRepository->findByEntityTypeAndEntityId($collectionnableSrc->getType(), $collectionnableSrc->getId(), false);

		// Transfer entries
		foreach ($entries as $entry) {
			$entry->setEntityType($collectionnableDest->getType());
			$entry->setEntityId($collectionnableDest->getId());

			// Update collectionnable collection count
			if ($entry->getCollection()->getIsPublic()) {
				$collectionnableSrc->incrementPublicCollectionCount(-1);
				$collectionnableDest->incrementPublicCollectionCount(1);
			} else {
				$collectionnableSrc->incrementPrivateCollectionCount(-1);
				$collectionnableDest->incrementPrivateCollectionCount(1);
			}

		}

		if ($flush) {
			$om->flush();
		}
	}

}