<?php

namespace Ladb\CoreBundle\Manager\Offer;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Offer\Offer;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\AbstractAuthoredPublicationManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\ReportableUtils;
use Ladb\CoreBundle\Utils\ViewableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;

class OfferManager extends AbstractAuthoredPublicationManager {

	const NAME = 'ladb_core.offer_offer_manager';

	/////

	public function publish(Offer $offer, $flush = true) {

        $offer->getUser()->getMeta()->incrementPrivateOfferCount(-1);
        $offer->getUser()->getMeta()->incrementPublicOfferCount();

        parent::publishPublication($offer, $flush);
	}

	public function unpublish(Offer $offer, $flush = true) {

        $offer->getUser()->getMeta()->incrementPrivateOfferCount();
        $offer->getUser()->getMeta()->incrementPublicOfferCount(-1);

        parent::unpublishPublication($offer, $flush);
	}

	public function delete(Offer $offer, $withWitness = true, $flush = true) {

		// Decrement user offer count
		if ($offer->getIsDraft()) {
			$offer->getUser()->getMeta()->incrementPrivateOfferCount(-1);
		} else {
			$offer->getUser()->getMeta()->incrementPublicOfferCount(-1);
		}

        parent::deletePublication($offer, $withWitness, $flush);
	}

	//////

	public function changeOwner(Offer $offer, User $user, $flush = true) {
		parent::changeOwnerPublication($offer, $user, $flush);
	}

	protected function updateUserCounterAfterChangeOwner(User $user, $by, $isPrivate) {
		if ($isPrivate) {
			$user->getMeta()->incrementPrivateOfferCount($by);
		} else {
			$user->getMeta()->incrementPublicOfferCount($by);
		}
	}

	/////

	public function convertToQuestion(Offer $offer, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new offer

		$question = new \Ladb\CoreBundle\Entity\Qa\Question();
		$question->setCreatedAt($offer->getCreatedAt());
		$question->setUpdatedAt($offer->getUpdatedAt());
		$question->setChangedAt($offer->getChangedAt());
		$question->setVisibility($offer->getVisibility());
		$question->setIsDraft($offer->getIsDraft());
		$question->setTitle($offer->getTitle());
		$question->setUser($offer->getUser());

		$blockBodiedUtils = $this->get(BlockBodiedUtils::NAME);
		$blockBodiedUtils->copyBlocksTo($offer, $question);

		if ($offer->getPictures()->count() > 0) {

			$textBlock = new \Ladb\CoreBundle\Entity\Core\Block\Text();
			$textBlock->setBody('Images');
			$textBlock->setSortIndex(0);
			$question->addBodyBlock($textBlock);

			$galleryBlock = new \Ladb\CoreBundle\Entity\Core\Block\Gallery();
			foreach ($offer->getPictures() as $picture) {
				$galleryBlock->addPicture($picture);
			}
			$galleryBlock->setSortIndex(1);
			$question->addBodyBlock($galleryBlock);

		}

		foreach ($offer->getTags() as $tag) {
			$question->addTag($tag);
		}

		// Setup question's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
		$fieldPreprocessorUtils->preprocessFields($question);

		// Persist question to generate ID
		$om->persist($question);
		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED_FROM_CONVERT, new PublicationEvent($question));

		// User counter
		if ($question->getIsDraft()) {
			$question->getUser()->getMeta()->incrementPrivateQuestionCount(1);
		} else {
			$question->getUser()->getMeta()->incrementPublicQuestionCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::NAME);
		$viewableUtils->transferViews($offer, $question, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::NAME);
		$likableUtils->transferLikes($offer, $question, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->transferComments($offer, $question, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$watchableUtils->transferWatches($offer, $question, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::NAME);
		$reportableUtils->transferReports($offer, $question, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->transferPublishActivities($offer->getType(), $offer->getId(), $question->getType(), $question->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::NAME);
		$witnessManager->createConvertedByPublication($offer, $question, false);

		// Delete the offer
		$this->delete($offer, false, false);

		if ($flush) {
			$om->flush();
		}

		return $question;
	}

}