<?php

namespace Ladb\CoreBundle\Manager;

use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\DraftableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\ReportableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\ReportableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;

abstract class AbstractPublicationManager extends AbstractManager {

	/////

	public function lock(AbstractPublication $publication, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$publication->setIsLocked(true);

		if ($flush) {
			$om->flush();
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_LOCKED, new PublicationEvent($publication));

	}

	public function unlock(AbstractPublication $publication, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$publication->setIsLocked(false);

		if ($flush) {
			$om->flush();
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_UNLOCKED, new PublicationEvent($publication));

	}

	/////

	protected function publishPublication(AbstractPublication $publication, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$publication->setCreatedAt(new \DateTime());
		$publication->setChangedAt(new \DateTime());

		if ($publication instanceof HiddableInterface) {
			$publication->setVisibility(HiddableInterface::VISIBILITY_PUBLIC);
		}

		if ($publication instanceof DraftableInterface) {
			$publication->setIsDraft(false);
		}

		// Delete the witness (if it exists)
		$witnessManager = $this->get(WitnessManager::NAME);
		$witnessManager->deleteByPublication($publication);

		if ($flush) {
			$om->flush();
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_PUBLISHED, new PublicationEvent($publication));

	}

	protected function unpublishPublication(AbstractPublication $publication, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		if ($publication instanceof HiddableInterface) {
			$publication->setVisibility(HiddableInterface::VISIBILITY_PRIVATE);
		}

		if ($publication instanceof DraftableInterface) {
			$publication->setIsDraft(true);
		}

		// Create the witness
		$witnessManager = $this->get(WitnessManager::NAME);
		$witnessManager->createUnpublishedByPublication($publication, false);

		if ($flush) {
			$om->flush();
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_UNPUBLISHED, new PublicationEvent($publication));

	}

	protected function deletePublication(AbstractPublication $publication, $withWitness = true, $flush = true) {

		if ($publication instanceof WatchableInterface) {
			// Delete watches
			$watchableUtils = $this->get(WatchableUtils::NAME);
			$watchableUtils->deleteWatches($publication, false);
		}

		if ($publication instanceof LikableInterface) {
			// Delete likes
			$likableUtils = $this->get(LikableUtils::NAME);
			$likableUtils->deleteLikes($publication, false);
		}

		if ($publication instanceof CommentableInterface) {
			// Delete comments
			$commentableUtils = $this->get(CommentableUtils::NAME);
			$commentableUtils->deleteComments($publication, false);
		}

		if ($publication instanceof ReportableInterface) {
			// Delete reports
			$reportableUtils = $this->get(ReportableUtils::NAME);
			$reportableUtils->deleteReports($publication, false);
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_DELETED, new PublicationEvent($publication));

		if ($withWitness) {

			// Create the witness
			$witnessManager = $this->get(WitnessManager::NAME);
			$witnessManager->createDeletedByPublication($publication, false);

		}

		parent::deleteEntity($publication, $flush);
	}

}