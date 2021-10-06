<?php

namespace App\Manager\Collection;

use App\Entity\Collection\Label;
use App\Entity\Collection\Part;
use App\Entity\Collection\Run;
use App\Entity\Collection\Task;
use App\Entity\Collection\Collection;
use App\Manager\AbstractPublicationManager;
use App\Model\CollectionnableInterface;
use App\Utils\TypableUtils;

class CollectionManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.collection_collection_manager';

	/////

	public function publish(Collection $collection, $flush = true) {

		$collection->getUser()->getMeta()->incrementPrivateCollectionCount(-1);
		$collection->getUser()->getMeta()->incrementPublicCollectionCount();

		// Reset collectionnables collection counters
		$typableUtils = $this->get(TypableUtils::class);
		foreach ($collection->getEntries() as $entry) {

			$collectionnable = $typableUtils->findTypable($entry->getEntityType(), $entry->getEntityId());
			if (!is_null($entry) && $collectionnable instanceof CollectionnableInterface) {

				// Update collectionnable collection count
				$collectionnable->incrementPrivateCollectionCount(-1);
				$collectionnable->incrementPublicCollectionCount();

			}

		}

		parent::publishPublication($collection, $flush);
	}

	public function unpublish(Collection $collection, $flush = true) {

		$collection->getUser()->getMeta()->incrementPrivateCollectionCount(1);
		$collection->getUser()->getMeta()->incrementPublicCollectionCount(-1);

		// Reset collectionnables collection counters
		$typableUtils = $this->get(TypableUtils::class);
		foreach ($collection->getEntries() as $entry) {

			$collectionnable = $typableUtils->findTypable($entry->getEntityType(), $entry->getEntityId());
			if (!is_null($entry) && $collectionnable instanceof CollectionnableInterface) {

				// Update collectionnable collection count
				$collectionnable->incrementPrivateCollectionCount();
				$collectionnable->incrementPublicCollectionCount(-1);

			}

		}

		parent::unpublishPublication($collection, $flush);
	}

	public function delete(Collection $collection, $withWitness = true, $flush = true) {

		// Decrement user collection count
		if ($collection->getIsPrivate()) {
			$collection->getUser()->getMeta()->incrementPrivateCollectionCount(-1);
		} else {
			$collection->getUser()->getMeta()->incrementPublicCollectionCount(-1);
		}

		// Reset collectionnables collection counters
		$typableUtils = $this->get(TypableUtils::class);
		foreach ($collection->getEntries() as $entry) {

			$collectionnable = $typableUtils->findTypable($entry->getEntityType(), $entry->getEntityId());
			if (!is_null($entry) && $collectionnable instanceof CollectionnableInterface) {

				// Update collectionnable collection count
				if ($collection->getIsPublic()) {
					$collectionnable->incrementPublicCollectionCount(-1);
				} else {
					$collectionnable->incrementPrivateCollectionCount(-1);
				}

			}

		}

		parent::deletePublication($collection, $withWitness, $flush);
	}

}