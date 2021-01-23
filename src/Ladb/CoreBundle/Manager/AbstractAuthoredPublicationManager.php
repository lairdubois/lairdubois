<?php

namespace Ladb\CoreBundle\Manager;

use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;
use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\Feedback;
use Ladb\CoreBundle\Entity\Core\Like;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\FeedbackableInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Utils\SearchUtils;

abstract class AbstractAuthoredPublicationManager extends AbstractPublicationManager {

	protected function changeOwnerPublication(AbstractAuthoredPublication $publication, User $targetUser, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$originUser = $publication->getUser();

		// Change publication's main picture user
		if ($publication instanceof PicturedInterface) {
			if (!is_null($publication->getMainPicture())) {
				$publication->getMainPicture()->setUser($targetUser);
			}
		}
		// Change publication's pictures user
		if ($publication instanceof MultiPicturedInterface) {
			foreach ($publication->getPictures() as $picture) {
				$picture->setUser($targetUser);
			}
		}

		// Change publication body blocks picture's user
		if ($publication instanceof BlockBodiedInterface) {
			foreach ($publication->getBodyBlocks() as $bodyBlock) {
				if ($bodyBlock instanceof Gallery) {
					foreach ($bodyBlock->getPictures() as $picture) {
						$picture->setUser($targetUser);
					}
				}
			}
		}

		// Change sub publications user
		$subPublications = $publication->getSubPublications();
		if (!is_null($subPublications)) {
			foreach ($publication->getSubPublications() as $subPublication) {

				if ($publication instanceof AuthoredInterface && $publication->getUser() != $originUser) {
					continue;	// Skip if owner is not the same as parent user
				}

				// Change sub publication body blocks picture's user
				if ($subPublication instanceof BlockBodiedInterface) {
					foreach ($subPublication->getBodyBlocks() as $bodyBlock) {
						if ($bodyBlock instanceof Gallery) {
							foreach ($bodyBlock->getPictures() as $picture) {
								$picture->setUser($targetUser);
							}
						}
					}
				}

			}
		}

		// Change publication user
		$publication->setUser($targetUser);

		// Update likes entityUser
		$likeRepository = $om->getRepository(Like::CLASS_NAME);
		$likes = $likeRepository->findByEntityTypeAndEntityId($publication->getType(), $publication->getId());
		foreach ($likes as $like) {
			$like->setEntityUser($targetUser);
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