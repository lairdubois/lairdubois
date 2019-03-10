<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Collection\Collection;
use Ladb\CoreBundle\Entity\Collection\Entry;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;

class CollectionnableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.collectionnable_utils';

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

}