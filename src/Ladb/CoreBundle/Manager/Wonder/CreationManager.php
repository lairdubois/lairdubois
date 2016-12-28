<?php

namespace Ladb\CoreBundle\Manager\Wonder;

use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\WitnessManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\ReportableUtils;
use Ladb\CoreBundle\Utils\ViewableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;

class CreationManager extends AbstractWonderManager {

	const NAME = 'ladb_core.creation_manager';

	/////

	public function publish(Creation $creation, $flush = true) {

		$creation->getUser()->incrementDraftCreationCount(-1);
		$creation->getUser()->incrementPublishedCreationCount();

		// Plans counter update
		foreach ($creation->getPlans() as $plan) {
			$plan->incrementCreationCount(1);
		}

		// Howtos counter update
		foreach ($creation->getHowtos() as $howto) {
			$howto->incrementCreationCount(1);
		}

		// Inspirations counter update
		foreach ($creation->getInspirations() as $inspiration) {
			$inspiration->incrementReboundCount(1);
		}

		parent::publishPublication($creation, $flush);
	}

	public function unpublish(Creation $creation, $flush = true) {

		$creation->getUser()->incrementDraftCreationCount(1);
		$creation->getUser()->incrementPublishedCreationCount(-1);

		// Plans counter update
		foreach ($creation->getPlans() as $plan) {
			$plan->incrementCreationCount(-1);
		}

		// Howtos counter update
		foreach ($creation->getHowtos() as $howto) {
			$howto->incrementCreationCount(-1);
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
			$creation->getUser()->incrementDraftCreationCount(-1);
		} else {
			$creation->getUser()->incrementPublishedCreationCount(-1);
		}

		// Unlink plans
		foreach ($creation->getPlans() as $plan) {
			$creation->removePlan($plan);
		}

		// Unlink howtos
		foreach ($creation->getHowtos() as $howto) {
			$creation->removeHowto($howto);
		}

		// Unlink inspirations
		foreach ($creation->getInspirations() as $inspiration) {
			$creation->removeInspiration($inspiration);
		}

		parent::deleteWonder($creation, $withWitness, $flush);
	}

	public function convertToWorkshop(Creation $creation, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new workshop

		$workshop = new \Ladb\CoreBundle\Entity\Wonder\Workshop();
		$workshop->setCreatedAt($creation->getCreatedAt());
		$workshop->setUpdatedAt($creation->getUpdatedAt());
		$workshop->setChangedAt($creation->getChangedAt());
		$workshop->setIsDraft($creation->getIsDraft());
		$workshop->setTitle($creation->getTitle());
		$workshop->setUser($creation->getUser());
		$workshop->setMainPicture($creation->getMainPicture());
		$workshop->setBody($creation->getBody());
		$workshop->setLicense(new \Ladb\CoreBundle\Entity\License($creation->getLicense()->getAllowDerivs(), $creation->getLicense()->getShareAlike(), $creation->getLicense()->getAllowCommercial()));

		foreach ($creation->getPictures() as $picture) {
			$workshop->addPicture($picture);
		}

		foreach ($creation->getTags() as $tag) {
			$workshop->addTag($tag);
		}

		// transfer plans
		foreach ($creation->getPlans() as $plan) {
			$workshop->addPlan($plan);
			$creation->removePlan($plan);
		}

		// transfer howtos
		foreach ($creation->getHowtos() as $howto) {
			$workshop->addHowto($howto);
			$creation->removeHowto($howto);
		}

		// Setup workshop's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
		$fieldPreprocessorUtils->preprocessFields($workshop);

		// Persist workshop to generate ID
		$om->persist($workshop);
		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED_FROM_CONVERT, new PublicationEvent($workshop));

		// User counter
		if ($workshop->getIsDraft()) {
			$workshop->getUser()->incrementDraftWorkshopCount(1);
		} else {
			$workshop->getUser()->incrementPublishedWorkshopCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::NAME);
		$viewableUtils->transferViews($creation, $workshop, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::NAME);
		$likableUtils->transferLikes($creation, $workshop, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->transferComments($creation, $workshop, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$watchableUtils->transferWatches($creation, $workshop, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::NAME);
		$reportableUtils->transferReports($creation, $workshop, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->transferPublishActivities($creation->getType(), $creation->getId(), $workshop->getType(), $workshop->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::NAME);
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

		$article = new \Ladb\CoreBundle\Entity\Howto\Article();
		$article->setTitle('Le projet');
		$article->setIsDraft(false);

		if ($creation->getPictures()->count() > 1) {

			$textBlock = new \Ladb\CoreBundle\Entity\Block\Text();
			$textBlock->setBody('Images du projet');
			$textBlock->setSortIndex(0);
			$article->addBodyBlock($textBlock);

			$galleryBlock = new \Ladb\CoreBundle\Entity\Block\Gallery();
			foreach ($creation->getPictures() as $picture) {
				$galleryBlock->addPicture($picture);
			}
			$galleryBlock->setSortIndex(1);
			$article->addBodyBlock($galleryBlock);

		}

		$blockBodiedUtils = $this->get(BlockBodiedUtils::NAME);
		$blockBodiedUtils->copyBlocksTo($creation, $article);

		$howto = new \Ladb\CoreBundle\Entity\Howto\Howto();
		$howto->setCreatedAt($creation->getCreatedAt());
		$howto->setUpdatedAt($creation->getUpdatedAt());
		$howto->setChangedAt($creation->getChangedAt());
		$howto->setIsDraft($creation->getIsDraft());
		$howto->setTitle($creation->getTitle());
		$howto->setUser($creation->getUser());
		$howto->setMainPicture($creation->getMainPicture());
		$howto->setBody('Projet de création.');
		$howto->setLicense(new \Ladb\CoreBundle\Entity\License($creation->getLicense()->getAllowDerivs(), $creation->getLicense()->getShareAlike(), $creation->getLicense()->getAllowCommercial()));

		$article->setHowto($howto);		// Workaround to $howto->addArticle($article); because it generates a constraint violation on $this->delete($creation, false, false);
		if ($howto->getIsDraft()) {
			$howto->incrementPublishedArticleCount();
		} else {
			$howto->incrementDraftArticleCount();
		}
		$article->setSortIndex(PHP_INT_MAX);	// Default sort index is max value = new articles at the end of the list

		foreach ($creation->getTags() as $tag) {
			$howto->addTag($tag);
		}

		// Transfer plans
		foreach ($creation->getPlans() as $plan) {
			$howto->addPlan($plan);
		}

		// Setup howto's and article's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
		$fieldPreprocessorUtils->preprocessFields($howto);
		$fieldPreprocessorUtils->preprocessFields($article);

		// Persist howto to generate ID
		$om->persist($howto);
		$om->persist($article);
		$om->flush();

		// Dispatch publications event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED_FROM_CONVERT, new PublicationEvent($howto));

		// User counter
		if ($howto->getIsDraft()) {
			$howto->getUser()->incrementDraftHowtoCount(1);
		} else {
			$howto->getUser()->incrementPublishedHowtoCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::NAME);
		$viewableUtils->transferViews($creation, $howto, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::NAME);
		$likableUtils->transferLikes($creation, $howto, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->transferComments($creation, $howto, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$watchableUtils->transferWatches($creation, $howto, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::NAME);
		$reportableUtils->transferReports($creation, $howto, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->transferPublishActivities($creation->getType(), $creation->getId(), $howto->getType(), $howto->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::NAME);
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

		$findContent = new \Ladb\CoreBundle\Entity\Find\Content\Gallery();
		$findContent->setLocation('');
		foreach ($creation->getPictures() as $picture) {
			$findContent->addPicture($picture);
		}

		$find = new \Ladb\CoreBundle\Entity\Find\Find();
		$find->setCreatedAt($creation->getCreatedAt());
		$find->setUpdatedAt($creation->getUpdatedAt());
		$find->setChangedAt($creation->getChangedAt());
		$find->setKind(\Ladb\CoreBundle\Entity\Find\Find::KIND_GALLERY);
		$find->setIsDraft($creation->getIsDraft());
		$find->setTitle($creation->getTitle());
		$find->setUser($creation->getUser());
		$find->setContentType(\Ladb\CoreBundle\Entity\Find\Find::CONTENT_TYPE_GALLERY);
		$find->setContent($findContent);
		$find->setMainPicture($creation->getMainPicture());
		$find->setBody($creation->getBody());

		foreach ($creation->getTags() as $tag) {
			$find->addTag($tag);
		}

		// Setup find's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
		$fieldPreprocessorUtils->preprocessFields($find);

		// Persist find to generate ID
		$om->persist($find);
		$om->flush();

		// Dispatch publications event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED_FROM_CONVERT, new PublicationEvent($find));

		// User counter
		if ($find->getIsDraft()) {
			$find->getUser()->incrementDraftFindCount(1);
		} else {
			$find->getUser()->incrementPublishedFindCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::NAME);
		$viewableUtils->transferViews($creation, $find, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::NAME);
		$likableUtils->transferLikes($creation, $find, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->transferComments($creation, $find, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$watchableUtils->transferWatches($creation, $find, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::NAME);
		$reportableUtils->transferReports($creation, $find, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->transferPublishActivities($creation->getType(), $creation->getId(), $find->getType(), $find->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::NAME);
		$witnessManager->createConvertedByPublication($creation, $find, false);

		// Delete the creation
		$this->delete($creation, false, false);

		if ($flush) {
			$om->flush();
		}

		return $find;
	}

}