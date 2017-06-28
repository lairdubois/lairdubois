<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Qa\Answer;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\VotableUtils;

class AnswerManager extends AbstractPublicationManager {

	const NAME = 'ladb_core.qa_answer_manager';

	/////

	public function publish(Answer $answer, $flush = true) {

		$question = $answer->getQuestion();

		$question->setChangedAt(new \DateTime());

		parent::publishPublication($answer, $flush);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($question));

	}

	public function unpublish(Answer $answer, $flush = true) {
		parent::unpublishPublication($answer, $flush);
	}

	public function delete(Answer $answer, $withWitness = true, $flush = true) {

		$question = $answer->getQuestion();
		$question->incrementAnswerCount(-1);

		// Delete votes
		$votableUtils = $this->get(VotableUtils::NAME);
		$votableUtils->deleteVotes($answer, $question, false);

		parent::deletePublication($answer, $withWitness, $flush);

		$question->removeAnswer($answer);
	}

}