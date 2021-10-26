<?php

namespace App\Manager;

use App\Entity\AbstractPublication;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Manager\Core\WitnessManager;
use App\Model\CollectionnableInterface;
use App\Model\CommentableInterface;
use App\Model\DraftableInterface;
use App\Model\HiddableInterface;
use App\Model\LikableInterface;
use App\Model\MentionSourceInterface;
use App\Model\ReportableInterface;
use App\Model\RepublishableInterface;
use App\Model\WatchableInterface;
use App\Utils\CollectionnableUtils;
use App\Utils\CommentableUtils;
use App\Utils\LikableUtils;
use App\Utils\MentionUtils;
use App\Utils\ReportableUtils;
use App\Utils\TypableUtils;
use App\Utils\WatchableUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


abstract class AbstractPublicationManager extends AbstractManager {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            'event_dispatcher' => '?'.EventDispatcherInterface::class,
            '?'.WitnessManager::class,
            '?'.CollectionnableUtils::class,
            '?'.CommentableUtils::class,
            '?'.LikableUtils::class,
            '?'.MentionUtils::class,
            '?'.ReportableUtils::class,
            '?'.TypableUtils::class,
            '?'.WatchableUtils::class,
        ));
    }

    /////

    public function lock(AbstractPublication $publication, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$publication->setIsLocked(true);

		if ($flush) {
			$om->flush();
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($publication), PublicationListener::PUBLICATION_LOCKED);

	}

	public function unlock(AbstractPublication $publication, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$publication->setIsLocked(false);

		if ($flush) {
			$om->flush();
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($publication), PublicationListener::PUBLICATION_UNLOCKED);

	}
	
	/////

	protected function publishPublication(AbstractPublication $publication, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		if ($publication instanceof RepublishableInterface) {
			if ($publication->getPublishCount() == 0) {
				$publication->setCreatedAt(new \DateTime());
			}
			$publication->incrementPublishCount();
		} else {
			$publication->setCreatedAt(new \DateTime());
		}
		$publication->setChangedAt(new \DateTime());

		if ($publication instanceof HiddableInterface) {
			$publication->setVisibility(HiddableInterface::VISIBILITY_PUBLIC);
		}

		if ($publication instanceof DraftableInterface) {
			$publication->setIsDraft(false);
		}

		// Process mentions
		$mentionUtils = $this->get(MentionUtils::class);
		$mentionUtils->processMentions($publication);

		// Delete the witness (if it exists)
		$witnessManager = $this->get(WitnessManager::class);
		$witnessManager->deleteByPublication($publication);

		if ($flush) {
			$om->flush();
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($publication), PublicationListener::PUBLICATION_PUBLISHED);

	}

	protected function unpublishPublication(AbstractPublication $publication, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		if ($publication instanceof HiddableInterface) {
			$publication->setVisibility(HiddableInterface::VISIBILITY_PRIVATE);
		}

		if ($publication instanceof DraftableInterface) {
			$publication->setIsDraft(true);
		}

		if ($publication instanceof CollectionnableInterface) {
			// Delete collection's entries
			$collectionnableUtils = $this->get(CollectionnableUtils::class);
			$collectionnableUtils->deleteEntries($publication, false);
		}

		// Delete mentions
		$mentionUtils = $this->container->get(MentionUtils::class);
		$mentionUtils->deleteMentions($publication);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::class);
		$witnessManager->createUnpublishedByPublication($publication, false);

		if ($flush) {
			$om->flush();
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($publication), PublicationListener::PUBLICATION_UNPUBLISHED);

	}

	protected function deletePublication(AbstractPublication $publication, $withWitness = true, $flush = true) {

		if ($publication instanceof MentionSourceInterface) {
			// Delete mentions
			$mentionUtils = $this->get(MentionUtils::class);
			$mentionUtils->deleteMentions($publication, false);
		}

		if ($publication instanceof WatchableInterface) {
			// Delete watches
			$watchableUtils = $this->get(WatchableUtils::class);
			$watchableUtils->deleteWatches($publication, false);
		}

		if ($publication instanceof LikableInterface) {
			// Delete likes
			$likableUtils = $this->get(LikableUtils::class);
			$likableUtils->deleteLikes($publication, false);
		}

		if ($publication instanceof CommentableInterface) {
			// Delete comments
			$commentableUtils = $this->get(CommentableUtils::class);
			$commentableUtils->deleteComments($publication, false);
		}

		if ($publication instanceof CollectionnableInterface) {
			// Delete collection's entries
			$collectionnableUtils = $this->get(CollectionnableUtils::class);
			$collectionnableUtils->deleteEntries($publication, false);
		}

		if ($publication instanceof ReportableInterface) {
			// Delete reports
			$reportableUtils = $this->get(ReportableUtils::class);
			$reportableUtils->deleteReports($publication, false);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($publication), PublicationListener::PUBLICATION_DELETED);

		if ($withWitness) {

			// Create the witness
			$witnessManager = $this->get(WitnessManager::class);
			$witnessManager->createDeletedByPublication($publication, false);

		}

		parent::deleteEntity($publication, $flush);
	}

}