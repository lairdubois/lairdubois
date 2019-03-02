<?php

namespace Ladb\CoreBundle\Manager\Qa;

use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Entity\Qa\Answer;
use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Manager\AbstractManager;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\MentionUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\VotableUtils;

class AnswerManager extends AbstractManager {

	const NAME = 'ladb_core.qa_answer_manager';

	/////

	public function delete(Answer $answer, $flush = true) {

		$question = $answer->getQuestion();

		// Drecrement question answer count
		$question->incrementAnswerCount(-1);

		if (!$question->getIsDraft()) {

			// Decrement user answer count
			$answer->getUser()->getMeta()->incrementAnswerCount(-1);

		}

		// Clear best answer
		if ($answer->getIsBestAnswer()) {
			$question->setBestAnswer(null);
		}

		/////

		// Delete mentions
		$mentionUtils = $this->get(MentionUtils::NAME);
		$mentionUtils->deleteMentions($answer, false);

		// Delete comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->deleteComments($answer, false);

		// Delete votes
		$votableUtils = $this->get(VotableUtils::NAME);
		$votableUtils->deleteVotes($answer, $question, false);

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->deleteActivitiesByAnswer($answer, false);

		// Compute answer counters
		$questionManager = $this->container->get(QuestionManager::NAME);
		$questionManager->computeAnswerCounters($question);

		parent::deleteEntity($answer, $flush);
	}

	/////

	public function converttocomment(Answer $answer, Question $question) {
		$om = $this->getDoctrine()->getManager();

		// Create a new comment on the question

		$comment = new Comment();
		$comment->setEntityId($question->getId());
		$comment->setEntityType($question->getType());
		$comment->setUser($answer->getUser());
		$comment->setCreatedAt($answer->getCreatedAt());
		$comment->setUpdatedAt($answer->getUpdatedAt());
		$comment->setBody($answer->getBody());

		foreach ($answer->getBodyBlocks() as $block) {
			if ($block instanceof Gallery) {
				$count = 0;
				foreach ($block->getPictures() as $picture) {
					$comment->addPicture($picture);
					if ($count++ >= 4) {
						break;
					}
				}
			}
		}

		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
		$fieldPreprocessorUtils->preprocessBodyField($comment);

		// Comment counters

		$question->incrementCommentCount();
		$answer->getUser()->getMeta()->incrementCommentCount();

		$om->persist($comment);

		// Create activity
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->createCommentActivity($comment, false);

		// Remove answer

		$this->delete($answer, true);

		// Update index
		if ($question instanceof IndexableInterface) {
			$searchUtils = $this->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($question);
		}

	}

}