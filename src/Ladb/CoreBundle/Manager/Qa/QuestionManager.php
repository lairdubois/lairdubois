<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Offer\Offer;
use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\AbstractAuthoredPublicationManager;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\ReportableUtils;
use Ladb\CoreBundle\Utils\ViewableUtils;
use Ladb\CoreBundle\Utils\VotableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;

class QuestionManager extends AbstractAuthoredPublicationManager {

	const NAME = 'ladb_core.qa_question_manager';

	/////

	public function publish(Question $question, $flush = true) {

		$question->getUser()->getMeta()->incrementPrivateQuestionCount(-1);
		$question->getUser()->getMeta()->incrementPublicQuestionCount();

		// Creations counter update
		foreach ($question->getCreations() as $creation) {
			$creation->incrementQuestionCount(1);
		}

		// Plans counter update
		foreach ($question->getPlans() as $plan) {
			$plan->incrementQuestionCount(1);
		}

		// Howto counter update
		foreach ($question->getHowtos() as $howto) {
			$howto->incrementQuestionCount(1);
		}

		foreach ($question->getAnswers() as $answer) {

			// Increment user answer count
			$answer->getUser()->getMeta()->incrementAnswerCount(1);

			// Increment users comment counters
			$commentableUtils = $this->container->get(CommentableUtils::NAME);
			$commentableUtils->incrementUsersCommentCount($answer, 1);

			// Increment users vote counters
			$votableUtils = $this->container->get(VotableUtils::NAME);
			$votableUtils->incrementUsersVoteCount($answer, 1);

		}

		parent::publishPublication($question, $flush);
	}

	public function unpublish(Question $question, $flush = true) {

		$question->getUser()->getMeta()->incrementPrivateQuestionCount(1);
		$question->getUser()->getMeta()->incrementPublicQuestionCount(-1);

		// Creations counter update
		foreach ($question->getCreations() as $creation) {
			$creation->incrementQuestionCount(-1);
		}

		// Plans counter update
		foreach ($question->getPlans() as $plan) {
			$plan->incrementQuestionCount(-1);
		}

		// Howto counter update
		foreach ($question->getHowtos() as $howto) {
			$howto->incrementQuestionCount(-1);
		}

		foreach ($question->getAnswers() as $answer) {

			// Decrement user answer count
			$answer->getUser()->getMeta()->incrementAnswerCount(-1);

			// Decrement users comment counters
			$commentableUtils = $this->container->get(CommentableUtils::NAME);
			$commentableUtils->incrementUsersCommentCount($answer, -1);

			// Decrement users vote counters
			$votableUtils = $this->container->get(VotableUtils::NAME);
			$votableUtils->incrementUsersVoteCount($answer, -1);

		}

		parent::unpublishPublication($question, $flush);
	}

	public function delete(Question $question, $withWitness = true, $flush = true) {

		// Decrement user creation count
		if ($question->getIsDraft()) {
			$question->getUser()->getMeta()->incrementPrivateQuestionCount(-1);
		} else {
			$question->getUser()->getMeta()->incrementPublicQuestionCount(-1);
		}

		// Unlink creations
		foreach ($question->getCreations() as $creation) {
			$creation->removeQuestion($question);
		}

		// Unlink plans
		foreach ($question->getPlans() as $plan) {
			$plan->removeQuestion($question);
		}

		// Unlink howtos
		foreach ($question->getHowtos() as $howto) {
			$howto->removeQuestion($question);
		}

		$answerManager = $this->get(AnswerManager::NAME);
		foreach ($question->getAnswers() as $answer) {

			// Delete answer
			$answerManager->delete($answer, false);

		}

		parent::deletePublication($question, $withWitness, $flush);
	}

	//////

	public function changeOwner(Question $question, User $user, $flush = true) {
		parent::changeOwnerPublication($question, $user, $flush);
	}

	protected function updateUserCounterAfterChangeOwner(User $user, $by, $isPrivate) {
		if ($isPrivate) {
			$user->getMeta()->incrementPrivateQuestionCount($by);
		} else {
			$user->getMeta()->incrementPublicQuestionCount($by);
		}
	}

	/////

	public function convertToOffer(Question $question, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		// Create a new offer

		$offer = new \Ladb\CoreBundle\Entity\Offer\Offer();
		$offer->setCreatedAt($question->getCreatedAt());
		$offer->setUpdatedAt($question->getUpdatedAt());
		$offer->setChangedAt($question->getChangedAt());
		$offer->setVisibility($question->getVisibility());
		$offer->setIsDraft($question->getIsDraft());
		$offer->setTitle($question->getTitle());
		$offer->setUser($question->getUser());
		$offer->setKind(Offer::KIND_REQUEST);
		$offer->setPrice('');

		$blockBodiedUtils = $this->get(BlockBodiedUtils::NAME);
		$blockBodiedUtils->copyBlocksTo($question, $offer);

		foreach ($question->getTags() as $tag) {
			$offer->addTag($tag);
		}

		// Setup offer's htmlBody
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
		$fieldPreprocessorUtils->preprocessFields($offer);

		// Persist offer to generate ID
		$om->persist($offer);
		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED_FROM_CONVERT, new PublicationEvent($offer));

		// User counter
		if ($offer->getIsDraft()) {
			$offer->getUser()->getMeta()->incrementPrivateOfferCount(1);
		} else {
			$offer->getUser()->getMeta()->incrementPublicOfferCount(1);
		}

		// Transfer views
		$viewableUtils = $this->get(ViewableUtils::NAME);
		$viewableUtils->transferViews($question, $offer, false);

		// Transfer likes
		$likableUtils = $this->get(LikableUtils::NAME);
		$likableUtils->transferLikes($question, $offer, false);

		// Transfer comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->transferComments($question, $offer, false);

		// Transfer watches
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$watchableUtils->transferWatches($question, $offer, false);

		// transfer reports
		$reportableUtils = $this->get(ReportableUtils::NAME);
		$reportableUtils->transferReports($question, $offer, false);

		// Transfer publish activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->transferPublishActivities($question->getType(), $question->getId(), $offer->getType(), $offer->getId(), false);

		// Create the witness
		$witnessManager = $this->get(WitnessManager::NAME);
		$witnessManager->createConvertedByPublication($question, $offer, false);

		// Delete the question
		$this->delete($question, false, false);

		if ($flush) {
			$om->flush();
		}

		return $offer;
	}

	public function computeAnswerCounters(Question $question) {

		$positiveAnswerCount = 0;
		$nullAnswerCount = 0;
		$undeterminedAnswerCount = 0;
		$negativeAnswerCount = 0;

		foreach ($question->getAnswers() as $answer) {
			if ($answer->getVoteScore() > 0) {
				$positiveAnswerCount++;
			} else if ($answer->getVoteScore() < 0) {
				$negativeAnswerCount++;
			} else if ($answer->getVoteScore() == 0 && $answer->getPositiveVoteScore() > 0) {
				$undeterminedAnswerCount++;
			} else {
				$nullAnswerCount++;
			}
		}

		$question->setPositiveAnswerCount($positiveAnswerCount);
		$question->setNullAnswerCount($nullAnswerCount);
		$question->setUndeterminedAnswerCount($undeterminedAnswerCount);
		$question->setNegativeAnswerCount($negativeAnswerCount);

	}

}