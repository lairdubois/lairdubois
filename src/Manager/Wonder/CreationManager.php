<?php

namespace App\Manager\Wonder;

use App\Entity\Core\User;
use App\Entity\Howto\Article;
use App\Entity\Wonder\Creation;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Manager\Core\WitnessManager;
use App\Utils\ActivityUtils;
use App\Utils\BlockBodiedUtils;
use App\Utils\CommentableUtils;
use App\Utils\FeedbackableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\LikableUtils;
use App\Utils\ReportableUtils;
use App\Utils\ViewableUtils;
use App\Utils\WatchableUtils;

class CreationManager extends AbstractWonderManager {

	const NAME = 'ladb_core.wonder_creation_manager';

	/////

	public function publish(Creation $creation, $flush = true) {

		$creation->getUser()->getMeta()->incrementPrivateCreationCount(-1);
		$creation->getUser()->getMeta()->incrementPublicCreationCount();

		// Question counter update
		foreach ($creation->getQuestions() as $question) {
			$question->incrementCreationCount(1);
		}

		// Plans counter update
		foreach ($creation->getPlans() as $plan) {
			$plan->incrementCreationCount(1);
		}

		// Howtos counter update
		foreach ($creation->getHowtos() as $howto) {
			$howto->incrementCreationCount(1);
		}

		// Workflow counter update
		foreach ($creation->getWorkflows() as $workflow) {
			$workflow->incrementCreationCount(1);
		}

		// Providers counter update
		foreach ($creation->getProviders() as $provider) {
			$provider->incrementCreationCount(1);
		}

		// School counter update
		foreach ($creation->getSchools() as $school) {
			$school->incrementCreationCount(1);
		}

		// Inspirations counter update
		foreach ($creation->getInspirations() as $inspiration) {
			$inspiration->incrementReboundCount(1);
		}

		parent::publishPublication($creation, $flush);
	}

	public function unpublish(Creation $creation, $flush = true) {

		$creation->getUser()->getMeta()->incrementPrivateCreationCount(1);
		$creation->getUser()->getMeta()->incrementPublicCreationCount(-1);

		// Question counter update
		foreach ($creation->getQuestions() as $question) {
			$question->incrementCreationCount(-1);
		}

		// Plans counter update
		foreach ($creation->getPlans() as $plan) {
			$plan->incrementCreationCount(-1);
		}

		// Howtos counter update
		foreach ($creation->getHowtos() as $howto) {
			$howto->incrementCreationCount(-1);
		}

		// Workflows counter update
		foreach ($creation->getWorkflows() as $workflow) {
			$workflow->incrementCreationCount(-1);
		}

		// Providers counter update
		foreach ($creation->getProviders() as $provider) {
			$provider->incrementCreationCount(-1);
		}

		// School counter update
		foreach ($creation->getSchools() as $school) {
			$school->incrementCreationCount(-1);
		}

		// Inspirations counter update
		foreach ($creation->getInspirations() as $inspiration) {
			$inspiration->incrementReboundCount(-1);
		}

		// Disable spotlight if creation is spotlighted
		if (!is_null($creation->getSpotlight())) {
			$creation->getSpotlight()->setEnabled(false);
		}

		parent::unpublishPublication($creation, $flush);
	}

	public function delete(Creation $creation, $withWitness = true, $flush = true) {

		// Decrement user creation count
		if ($creation->getIsDraft()) {
			$creation->getUser()->getMeta()->incrementPrivateCreationCount(-1);
		} else {
			$creation->getUser()->getMeta()->incrementPublicCreationCount(-1);
		}

		// Unlink questions
		foreach ($creation->getQuestions() as $question) {
			$creation->removeQuestion($question);
		}

		// Unlink plans
		foreach ($creation->getPlans() as $plan) {
			$creation->removePlan($plan);
		}

		// Unlink howtos
		foreach ($creation->getHowtos() as $howto) {
			$creation->removeHowto($howto);
		}

		// Unlink workflows
		foreach ($creation->getWorkflows() as $workflow) {
			$creation->removeWorkflow($workflow);
		}

		// Unlink providers
		foreach ($creation->getProviders() as $provider) {
			$creation->removeProvider($provider);
		}

		// Unlink schools
		foreach ($creation->getSchools() as $school) {
			$creation->removeSchool($school);
		}

		// Unlink inspirations
		foreach ($creation->getInspirations() as $inspiration) {
			$creation->removeInspiration($inspiration);
		}

		// Delete feedbacks
		$feedbackableUtils = $this->get(FeedbackableUtils::class);
		$feedbackableUtils->deleteFeedbacks($creation, false);

		parent::deleteWonder($creation, $withWitness, $flush);
	}

	/////

	public function convertToWorkshop(Creation $creation, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new workshop

		$workshop = new \App\Entity\Wonder\Workshop();
		$workshop->setCreatedAt($creation->getCreatedAt());
		$workshop->setUpdatedAt($creation->getUpdatedAt());
		$workshop->setChangedAt($creation->getChangedAt());
		$workshop->setVisibility($creation->getVisibility());
		$workshop->setIsDraft($creation->getIsDraft());
		$workshop->setTitle($creation->getTitle());
		$workshop->setUser($creation->getUser());
		$workshop->setMainPicture($creation->getMainPicture());
		$workshop->setLicense(new \App\Entity\Core\License($creation->getLicense()->getAllowDerivs(), $creation->getLicense()->getShareAlike(), $creation->getLicense()->getAllowCommercial()));

		$blockBodiedUtils = $this->get(BlockBodiedUtils::class);
		$blockBodiedUtils->copyBlocksTo($creation, $workshop);

		foreach ($creation->getPictures() as $picture) {
			$workshop->addPicture($picture);
		}

		foreach ($creation->getTags() as $tag) {
			$workshop->addTag($tag);
		}

		// transfer plans
		foreach ($creation->getPlans() as $plan) {
			$workshop->addPlan($plan);
		}

		// transfer howtos
		foreach ($creation->getHowtos() as $howto) {
			$workshop->addHowto($howto);
		}

		// transfer workflows
		foreach ($creation->getWorkflows() as $workflow) {
			$workshop->addWorkflow($workflow);
		}

		// Setup workshop's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
		$fieldPreprocessorUtils->preprocessFields($workshop);

		// Persist workshop to generate ID
		$om->persist($workshop);
		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($workshop), PublicationListener::PUBLICATION_CREATED_FROM_CONVERT);

		// User counter
		if ($workshop->getIsDraft()) {
			$workshop->getUser()->getMeta()->incrementPrivateWorkshopCount(1);
		} else {
			$workshop->getUser()->getMeta()->incrementPublicWorkshopCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::class);
		$viewableUtils->transferViews($creation, $workshop, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::class);
		$likableUtils->transferLikes($creation, $workshop, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::class);
		$commentableUtils->transferComments($creation, $workshop, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::class);
		$watchableUtils->transferWatches($creation, $workshop, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::class);
		$reportableUtils->transferReports($creation, $workshop, false);

		// Transfer feedbacks
		$feedbackableUtils = $this->get(FeedbackableUtils::class);
		$feedbackableUtils->transferFeedbacks($creation, $workshop, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->transferPublishActivities($creation->getType(), $creation->getId(), $workshop->getType(), $workshop->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::class);
		$witnessManager->createConvertedByPublication($creation, $workshop, false);

		// Delete the creation
		$this->delete($creation, false, false);

		if ($flush) {
			$om->flush();
		}

		return $workshop;
	}

	public function convertToHowto(Creation $creation, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new howto and its article

		$article = new \App\Entity\Howto\Article();
		$article->setTitle('Le projet');
		$article->setIsDraft(false);

		if ($creation->getPictures()->count() > 1) {

			$textBlock = new \App\Entity\Core\Block\Text();
			$textBlock->setBody('Images du projet');
			$textBlock->setSortIndex(0);
			$article->addBodyBlock($textBlock);

			$galleryBlock = new \App\Entity\Core\Block\Gallery();
			foreach ($creation->getPictures() as $picture) {
				$galleryBlock->addPicture($picture);
			}
			$galleryBlock->setSortIndex(1);
			$article->addBodyBlock($galleryBlock);

		}

		$blockBodiedUtils = $this->get(BlockBodiedUtils::class);
		$blockBodiedUtils->copyBlocksTo($creation, $article);

		$howto = new \App\Entity\Howto\Howto();
		$howto->setCreatedAt($creation->getCreatedAt());
		$howto->setUpdatedAt($creation->getUpdatedAt());
		$howto->setChangedAt($creation->getChangedAt());
		$howto->setVisibility($creation->getVisibility());
		$howto->setIsDraft($creation->getIsDraft());
		$howto->setTitle($creation->getTitle());
		$howto->setUser($creation->getUser());
		$howto->setMainPicture($creation->getMainPicture());
		$howto->setBody('Projet de création.');
		$howto->setLicense(new \App\Entity\Core\License($creation->getLicense()->getAllowDerivs(), $creation->getLicense()->getShareAlike(), $creation->getLicense()->getAllowCommercial()));

		$article->setHowto($howto);		// Workaround to $howto->addArticle($article); because it generates a constraint violation on $this->delete($creation, false, false);
		if ($howto->getIsDraft()) {
			$howto->incrementPublishedArticleCount();
		} else {
			$howto->incrementDraftArticleCount();
		}

		foreach ($creation->getTags() as $tag) {
			$howto->addTag($tag);
		}

		// Transfer questions
		foreach ($creation->getQuestions() as $question) {
			$howto->addQuestion($question);
		}

		// Transfer plans
		foreach ($creation->getPlans() as $plan) {
			$howto->addPlan($plan);
		}

		// transfer workflows
		foreach ($creation->getWorkflows() as $workflow) {
			$howto->addWorkflow($workflow);
		}

		// transfer providers
		foreach ($creation->getProviders() as $provider) {
			$howto->addProvider($provider);
		}

		// transfer school
		foreach ($creation->getSchools() as $school) {
			$howto->addSchool($school);
		}

		// Setup howto's and article's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
		$fieldPreprocessorUtils->preprocessFields($howto);
		$fieldPreprocessorUtils->preprocessFields($article);

		// Persist howto to generate ID
		$om->persist($howto);
		$om->persist($article);
		$om->flush();

		// Dispatch publications event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($howto), PublicationListener::PUBLICATION_CREATED_FROM_CONVERT);

		// User counter
		if ($howto->getIsDraft()) {
			$howto->getUser()->getMeta()->incrementPrivateHowtoCount(1);
		} else {
			$howto->getUser()->getMeta()->incrementPublicHowtoCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::class);
		$viewableUtils->transferViews($creation, $howto, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::class);
		$likableUtils->transferLikes($creation, $howto, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::class);
		$commentableUtils->transferComments($creation, $howto, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::class);
		$watchableUtils->transferWatches($creation, $howto, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::class);
		$reportableUtils->transferReports($creation, $howto, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->transferPublishActivities($creation->getType(), $creation->getId(), $howto->getType(), $howto->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::class);
		$witnessManager->createConvertedByPublication($creation, $howto, false);

		// Delete the creation
		$this->delete($creation, false, false);

		if ($flush) {
			$om->flush();
		}

		return $howto;
	}

	public function convertToFind(Creation $creation, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new find and its content

		$findContent = new \App\Entity\Find\Content\Gallery();
		foreach ($creation->getPictures() as $picture) {
			$findContent->addPicture($picture);
		}

		$find = new \App\Entity\Find\Find();
		$find->setCreatedAt($creation->getCreatedAt());
		$find->setUpdatedAt($creation->getUpdatedAt());
		$find->setChangedAt($creation->getChangedAt());
		$find->setKind(\App\Entity\Find\Find::KIND_GALLERY);
		$find->setVisibility($creation->getVisibility());
		$find->setIsDraft($creation->getIsDraft());
		$find->setTitle($creation->getTitle());
		$find->setUser($creation->getUser());
		$find->setContentType(\App\Entity\Find\Find::CONTENT_TYPE_GALLERY);
		$find->setContent($findContent);
		$find->setMainPicture($creation->getMainPicture());

		$blockBodiedUtils = $this->get(BlockBodiedUtils::class);
		$blockBodiedUtils->copyBlocksTo($creation, $find);

		foreach ($creation->getTags() as $tag) {
			$find->addTag($tag);
		}

		// Setup find's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
		$fieldPreprocessorUtils->preprocessFields($find);

		// Persist find to generate ID
		$om->persist($find);
		$om->flush();

		// Dispatch publications event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($find), PublicationListener::PUBLICATION_CREATED_FROM_CONVERT);

		// User counter
		if ($find->getIsDraft()) {
			$find->getUser()->getMeta()->incrementPrivateFindCount(1);
		} else {
			$find->getUser()->getMeta()->incrementPublicFindCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::class);
		$viewableUtils->transferViews($creation, $find, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::class);
		$likableUtils->transferLikes($creation, $find, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::class);
		$commentableUtils->transferComments($creation, $find, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::class);
		$watchableUtils->transferWatches($creation, $find, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::class);
		$reportableUtils->transferReports($creation, $find, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->transferPublishActivities($creation->getType(), $creation->getId(), $find->getType(), $find->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::class);
		$witnessManager->createConvertedByPublication($creation, $find, false);

		// Delete the creation
		$this->delete($creation, false, false);

		if ($flush) {
			$om->flush();
		}

		return $find;
	}

	public function convertToQuestion(Creation $creation, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new question

		$question = new \App\Entity\Qa\Question();
		$question->setCreatedAt($creation->getCreatedAt());
		$question->setUpdatedAt($creation->getUpdatedAt());
		$question->setChangedAt($creation->getChangedAt());
		$question->setVisibility($creation->getVisibility());
		$question->setIsDraft($creation->getIsDraft());
		$question->setTitle($creation->getTitle());
		$question->setUser($creation->getUser());

		$blockBodiedUtils = $this->get(BlockBodiedUtils::class);
		$blockBodiedUtils->copyBlocksTo($creation, $question);

		foreach ($creation->getTags() as $tag) {
			$question->addTag($tag);
		}

		// Setup question's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
		$fieldPreprocessorUtils->preprocessFields($question);

		// Persist question to generate ID
		$om->persist($question);
		$om->flush();

		// Dispatch publications event
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
		$viewableUtils->transferViews($creation, $question, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::class);
		$likableUtils->transferLikes($creation, $question, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::class);
		$commentableUtils->transferComments($creation, $question, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::class);
		$watchableUtils->transferWatches($creation, $question, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::class);
		$reportableUtils->transferReports($creation, $question, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->transferPublishActivities($creation->getType(), $creation->getId(), $question->getType(), $question->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::class);
		$witnessManager->createConvertedByPublication($creation, $question, false);

		// Delete the creation
		$this->delete($creation, false, false);

		if ($flush) {
			$om->flush();
		}

		return $question;
	}

	public function convertToOffer(Creation $creation, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new offer

		$offer = new \App\Entity\Offer\Offer();
		$offer->setCreatedAt($creation->getCreatedAt());
		$offer->setUpdatedAt($creation->getUpdatedAt());
		$offer->setChangedAt($creation->getChangedAt());
		$offer->setVisibility($creation->getVisibility());
		$offer->setIsDraft($creation->getIsDraft());
		$offer->setTitle($creation->getTitle());
		$offer->setUser($creation->getUser());
		$offer->setMainPicture($creation->getMainPicture());

		$offer->setKind(\App\Entity\Offer\Offer::KIND_REQUEST);
		$offer->setCategory(\App\Entity\Offer\Offer::CATEGORY_OTHER);
		$offer->setPrice('');

		$blockBodiedUtils = $this->get(BlockBodiedUtils::class);
		$blockBodiedUtils->copyBlocksTo($creation, $offer);

		foreach ($creation->getPictures() as $picture) {
			$offer->addPicture($picture);
		}

		foreach ($creation->getTags() as $tag) {
			$offer->addTag($tag);
		}

		// Setup offer's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
		$fieldPreprocessorUtils->preprocessFields($offer);

		// Persist offer to generate ID
		$om->persist($offer);
		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($offer), PublicationListener::PUBLICATION_CREATED_FROM_CONVERT);

		// User counter
		if ($offer->getIsDraft()) {
			$offer->getUser()->getMeta()->incrementPrivateOfferCount(1);
		} else {
			$offer->getUser()->getMeta()->incrementPublicOfferCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::class);
		$viewableUtils->transferViews($creation, $offer, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::class);
		$likableUtils->transferLikes($creation, $offer, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::class);
		$commentableUtils->transferComments($creation, $offer, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::class);
		$watchableUtils->transferWatches($creation, $offer, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::class);
		$reportableUtils->transferReports($creation, $offer, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->transferPublishActivities($creation->getType(), $creation->getId(), $offer->getType(), $offer->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::class);
		$witnessManager->createConvertedByPublication($creation, $offer, false);

		// Delete the creation
		$this->delete($creation, false, false);

		if ($flush) {
			$om->flush();
		}

		return $offer;
	}

	//////

	public function changeOwner(Creation $creation, User $user, $flush = true) {
		parent::changeOwnerPublication($creation, $user, $flush);
	}

	protected function updateUserCounterAfterChangeOwner(User $user, $by, $isPrivate) {
		if ($isPrivate) {
			$user->getMeta()->incrementPrivateCreationCount($by);
		} else {
			$user->getMeta()->incrementPublicCreationCount($by);
		}
	}

}