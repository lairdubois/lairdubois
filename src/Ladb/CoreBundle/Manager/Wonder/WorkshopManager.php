<?php

namespace Ladb\CoreBundle\Manager\Wonder;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Howto\Article;
use Ladb\CoreBundle\Entity\Wonder\Workshop;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FeedbackableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\ReportableUtils;
use Ladb\CoreBundle\Utils\ViewableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;

class WorkshopManager extends AbstractWonderManager {

	const NAME = 'ladb_core.wonder_workshop_manager';

	/////

	public function publish(Workshop $workshop, $flush = true) {

		$workshop->getUser()->getMeta()->incrementPrivateWorkshopCount(-1);
		$workshop->getUser()->getMeta()->incrementPublicWorkshopCount();

		// Plan counter update
		foreach ($workshop->getPlans() as $plan) {
			$plan->incrementWorkshopCount(1);
		}

		// Howtos counter update
		foreach ($workshop->getHowtos() as $howto) {
			$howto->incrementWorkshopCount(1);
		}

		parent::publishPublication($workshop, $flush);
	}

	public function unpublish(Workshop $workshop, $flush = true) {

		$workshop->getUser()->getMeta()->incrementPrivateWorkshopCount(1);
		$workshop->getUser()->getMeta()->incrementPublicWorkshopCount(-1);

		// Plan counter update
		foreach ($workshop->getPlans() as $plan) {
			$plan->incrementWorkshopCount(-1);
		}

		// Howtos counter update
		foreach ($workshop->getHowtos() as $howto) {
			$howto->incrementWorkshopCount(-1);
		}

		parent::unpublishPublication($workshop, $flush);
	}

	public function delete(Workshop $workshop, $withWitness = true, $flush = true) {

		// Decrement user workshop count
		if ($workshop->getIsDraft()) {
			$workshop->getUser()->getMeta()->incrementPrivateWorkshopCount(-1);
		} else {
			$workshop->getUser()->getMeta()->incrementPublicWorkshopCount(-1);
		}

		// Unlink plans
		foreach ($workshop->getPlans() as $plan) {
			$workshop->removePlan($plan);
		}

		// Unlink howtos
		foreach ($workshop->getHowtos() as $howto) {
			$workshop->removeHowto($howto);
		}

		parent::deleteWonder($workshop, $withWitness, $flush);
	}

	public function convertToCreation(Workshop $workshop, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new creation

		$creation = new \Ladb\CoreBundle\Entity\Wonder\Creation();
		$creation->setCreatedAt($workshop->getCreatedAt());
		$creation->setUpdatedAt($workshop->getUpdatedAt());
		$creation->setChangedAt($workshop->getChangedAt());
		$creation->setVisibility($workshop->getVisibility());
		$creation->setIsDraft($workshop->getIsDraft());
		$creation->setTitle($workshop->getTitle());
		$creation->setUser($workshop->getUser());
		$creation->setMainPicture($workshop->getMainPicture());
		$creation->setLicense(new \Ladb\CoreBundle\Entity\Core\License($workshop->getLicense()->getAllowDerivs(), $workshop->getLicense()->getShareAlike(), $workshop->getLicense()->getAllowCommercial()));

		$blockBodiedUtils = $this->get(BlockBodiedUtils::NAME);
		$blockBodiedUtils->copyBlocksTo($workshop, $creation);

		foreach ($workshop->getPictures() as $picture) {
			$creation->addPicture($picture);
		}

		foreach ($workshop->getTags() as $tag) {
			$creation->addTag($tag);
		}

		// transfer plans
		foreach ($workshop->getPlans() as $plan) {
			$creation->addPlan($plan);
		}

		// transfer howtos
		foreach ($workshop->getHowtos() as $howto) {
			$creation->addHowto($howto);
		}

		// transfer workflows
		foreach ($workshop->getWorkflows() as $workflow) {
			$creation->addWorkflow($workflow);
		}

		// Setup creation's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
		$fieldPreprocessorUtils->preprocessFields($creation);

		// Persist creation to generate ID
		$om->persist($creation);
		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED_FROM_CONVERT, new PublicationEvent($creation));

		// User counter
		if ($creation->getIsDraft()) {
			$creation->getUser()->getMeta()->incrementPrivateCreationCount(1);
		} else {
			$creation->getUser()->getMeta()->incrementPublicCreationCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::NAME);
		$viewableUtils->transferViews($workshop, $creation, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::NAME);
		$likableUtils->transferLikes($workshop, $creation, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->transferComments($workshop, $creation, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$watchableUtils->transferWatches($workshop, $creation, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::NAME);
		$reportableUtils->transferReports($workshop, $creation, false);

		// Transfer feedbacks
		$feedbackableUtils = $this->get(FeedbackableUtils::NAME);
		$feedbackableUtils->transferFeedbacks($workshop, $creation, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->transferPublishActivities($workshop->getType(), $workshop->getId(), $creation->getType(), $creation->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::NAME);
		$witnessManager->createConvertedByPublication($workshop, $creation, false);

		// Delete the workshop
		$this->delete($workshop, false, false);

		if ($flush) {
			$om->flush();
		}

		return $creation;
	}

	public function convertToHowto(Workshop $workshop, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new howto and its article

		$article = new \Ladb\CoreBundle\Entity\Howto\Article();
		$article->setTitle('Le projet');
		$article->setIsDraft(false);

		if ($workshop->getPictures()->count() > 1) {

			$textBlock = new \Ladb\CoreBundle\Entity\Core\Block\Text();
			$textBlock->setBody('Images du projet');
			$textBlock->setSortIndex(0);
			$article->addBodyBlock($textBlock);

			$galleryBlock = new \Ladb\CoreBundle\Entity\Core\Block\Gallery();
			foreach ($workshop->getPictures() as $picture) {
				$galleryBlock->addPicture($picture);
			}
			$galleryBlock->setSortIndex(1);
			$article->addBodyBlock($galleryBlock);

		}

		$blockBodiedUtils = $this->get(BlockBodiedUtils::NAME);
		$blockBodiedUtils->copyBlocksTo($workshop, $article);

		$howto = new \Ladb\CoreBundle\Entity\Howto\Howto();
		$howto->setCreatedAt($workshop->getCreatedAt());
		$howto->setUpdatedAt($workshop->getUpdatedAt());
		$howto->setChangedAt($workshop->getChangedAt());
		$howto->setVisibility($workshop->getVisibility());
		$howto->setIsDraft($workshop->getIsDraft());
		$howto->setTitle($workshop->getTitle());
		$howto->setUser($workshop->getUser());
		$howto->setMainPicture($workshop->getMainPicture());
		$howto->setBody('Projet d\'atelier'.($workshop->getArea() ? ' de '.$workshop->getArea().'m²' : '').($workshop->getLocation() ? ' à '.$workshop->getLocation() : '').'.');
		$howto->setLicense(new \Ladb\CoreBundle\Entity\Core\License($workshop->getLicense()->getAllowDerivs(), $workshop->getLicense()->getShareAlike(), $workshop->getLicense()->getAllowCommercial()));

		$article->setHowto($howto);		// Workaround to $howto->addArticle($article); because it generates a constraint violation on $this->delete($workshop, false, false);
		if ($howto->getIsDraft()) {
			$howto->incrementPublishedArticleCount();
		} else {
			$howto->incrementDraftArticleCount();
		}

		foreach ($workshop->getTags() as $tag) {
			$howto->addTag($tag);
		}

		// Transfer plans
		foreach ($workshop->getPlans() as $plan) {
			$howto->addPlan($plan);
		}

		// Transfer workflows
		foreach ($workshop->getWorkflows() as $workflow) {
			$howto->addWorkflow($workflow);
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
			$howto->getUser()->getMeta()->incrementPrivateHowtoCount(1);
		} else {
			$howto->getUser()->getMeta()->incrementPublicHowtoCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::NAME);
		$viewableUtils->transferViews($workshop, $howto, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::NAME);
		$likableUtils->transferLikes($workshop, $howto, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->transferComments($workshop, $howto, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$watchableUtils->transferWatches($workshop, $howto, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::NAME);
		$reportableUtils->transferReports($workshop, $howto, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->transferPublishActivities($workshop->getType(), $workshop->getId(), $howto->getType(), $howto->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::NAME);
		$witnessManager->createConvertedByPublication($workshop, $howto, false);

		// Delete the workshop
		$this->delete($workshop, false, false);

		if ($flush) {
			$om->flush();
		}

		return $howto;
	}

	//////

	public function changeOwner(Workshop $workshop, User $user, $flush = true) {
		parent::changeOwnerPublication($workshop, $user, $flush);
	}

	protected function updateUserCounterAfterChangeOwner(User $user, $by, $isPrivate) {
		if ($isPrivate) {
			$user->getMeta()->incrementPrivatePlanCount($by);
		} else {
			$user->getMeta()->incrementPublicPlanCount($by);
		}
	}

}