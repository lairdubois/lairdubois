<?php

namespace Ladb\CoreBundle\Manager;

use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;
use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Utils\SearchUtils;

abstract class AbstractAuthoredPublicationManager extends AbstractPublicationManager {

	protected function changeOwnerPublication(AbstractAuthoredPublication $publication, User $targetUser, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$originUser = null;
		if ($publication instanceof AuthoredInterface) {
			$originUser = $publication->getUser();
			$publication->setUser($targetUser);
		}
		if ($publication instanceof MultiPicturedInterface) {
			foreach ($publication->getPictures() as $picture) {
				$picture->setUser($targetUser);
			}
		}
		if ($publication instanceof BlockBodiedInterface) {
			foreach ($publication->getBodyBlocks() as $bodyBlock) {
				if ($bodyBlock instanceof Gallery) {
					foreach ($bodyBlock->getPictures() as $picture) {
						$picture->setUser($targetUser);
					}
				}
			}
		}

		if (!is_null($originUser)) {
			$this->updateUserCounterAfterChangeOwner($originUser, -1, $publication->getIsPrivate());
		}
		$this->updateUserCounterAfterChangeOwner($targetUser, 1, $publication->getIsPrivate());

		if ($publication instanceof IndexableInterface && $publication->isIndexable()) {

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($publication);

		}

		if ($flush) {
			$om->flush();
		}

	}

	protected abstract function updateUserCounterAfterChangeOwner(User $user, $by, $isPrivate);

}