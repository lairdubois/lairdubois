<?php

namespace App\Manager\Offer;

use App\Entity\Core\User;
use App\Entity\Offer\Offer;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Manager\AbstractAuthoredPublicationManager;
use App\Manager\Core\WitnessManager;
use App\Utils\ActivityUtils;
use App\Utils\BlockBodiedUtils;
use App\Utils\CommentableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\LikableUtils;
use App\Utils\ReportableUtils;
use App\Utils\ViewableUtils;
use App\Utils\WatchableUtils;

class OfferManager extends AbstractAuthoredPublicationManager {

	public function publish(Offer $offer, $flush = true) {

		$offer->getUser()->getMeta()->incrementPrivateOfferCount(-1);
		$offer->getUser()->getMeta()->incrementPublicOfferCount();

		parent::publishPublication($offer, $flush);
	}

	public function unpublish(Offer $offer, $flush = true) {

		$offer->getUser()->getMeta()->incrementPrivateOfferCount(1);
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

		$question = new \App\Entity\Qa\Question();
		$question->setCreatedAt($offer->getCreatedAt());
		$question->setUpdatedAt($offer->getUpdatedAt());
		$question->setChangedAt($offer->getChangedAt());
		$question->setVisibility($offer->getVisibility());
		$question->setIsDraft($offer->getIsDraft());
		$question->setTitle($offer->getTitle());
		$question->setUser($offer->getUser());

		$blockBodiedUtils = $this->get(BlockBodiedUtils::class);
		$blockBodiedUtils->copyBlocksTo($offer, $question);

		if ($offer->getPictures()->count() > 0) {

			$textBlock = new \App\Entity\Core\Block\Text();
			$textBlock->setBody('Images');
			$textBlock->setSortIndex(0);
			$question->addBodyBlock($textBlock);

			$galleryBlock = new \App\Entity\Core\Block\Gallery();
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
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
		$fieldPreprocessorUtils->preprocessFields($question);

		// Persist question to generate ID
		$om->persist($question);
		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($question), PublicationListener::PUBLICATION_CREATED_FROM_CONVERT);

		// User counter
		if ($question->getIsDraft()) {
			$question->getUser()->getMeta()->incrementPrivateQuestionCount(1);
		} else {
			$question->getUser()->getMeta()->incrementPublicQuestionCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::class);
		$viewableUtils->transferViews($offer, $question, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::class);
		$likableUtils->transferLikes($offer, $question, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::class);
		$commentableUtils->transferComments($offer, $question, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::class);
		$watchableUtils->transferWatches($offer, $question, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::class);
		$reportableUtils->transferReports($offer, $question, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->transferPublishActivities($offer->getType(), $offer->getId(), $question->getType(), $question->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::class);
		$witnessManager->createConvertedByPublication($offer, $question, false);

		// Delete the offer
		$this->delete($offer, false, false);

		if ($flush) {
			$om->flush();
		}

		return $question;
	}

}