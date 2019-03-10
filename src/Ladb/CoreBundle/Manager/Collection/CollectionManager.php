<?php

namespace Ladb\CoreBundle\Manager\Collection;

use Ladb\CoreBundle\Entity\Collection\Label;
use Ladb\CoreBundle\Entity\Collection\Part;
use Ladb\CoreBundle\Entity\Collection\Run;
use Ladb\CoreBundle\Entity\Collection\Task;
use Ladb\CoreBundle\Entity\Collection\Collection;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Utils\TypableUtils;

class CollectionManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.collection_manager';

	/////

	public function publish(Collection $collection, $flush = true) {

		$collection->getUser()->getMeta()->incrementPrivateCollectionCount(-1);
		$collection->getUser()->getMeta()->incrementPublicCollectionCount();

		// Reset collectionnables collection counters
		$typableUtils = $this->get(TypableUtils::NAME);
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
		$typableUtils = $this->get(TypableUtils::NAME);
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
		$typableUtils = $this->get(TypableUtils::NAME);
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