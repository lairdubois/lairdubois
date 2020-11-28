<?php

namespace Ladb\CoreBundle\Manager;

use Ladb\CoreBundle\Entity\AbstractDraftableAuthoredPublication;
use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\CollectionnableInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\DraftableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\LinkedToInterface;
use Ladb\CoreBundle\Model\MentionSourceInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\RepublishableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\MentionUtils;
use Ladb\CoreBundle\Utils\ReportableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;

abstract class AbstractAuthoredPublicationManager extends AbstractPublicationManager {

	protected function changeOwnerPublication(AbstractPublication $publication, User $targetUser, $flush = true) {
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

		$isPrivate = $publication instanceof HiddableInterface && $publication->getIsPrivate();
		if (!is_null($originUser)) {
			$this->updateUserCounterAfterChangeOwner($originUser, -1, $isPrivate);
		}
		$this->updateUserCounterAfterChangeOwner($targetUser, 1, $isPrivate);

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