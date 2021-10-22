<?php

namespace App\Manager\Qa;

use App\Entity\Core\Block\Gallery;
use App\Entity\Core\Comment;
use App\Entity\Qa\Answer;
use App\Entity\Qa\Question;
use App\Manager\AbstractManager;
use App\Model\IndexableInterface;
use App\Utils\ActivityUtils;
use App\Utils\CommentableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\MentionUtils;
use App\Utils\SearchUtils;
use App\Utils\VotableUtils;

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
		$mentionUtils = $this->get(MentionUtils::class);
		$mentionUtils->deleteMentions($answer, false);

		// Delete comments
		$commentableUtils = $this->get(CommentableUtils::class);
		$commentableUtils->deleteComments($answer, false);

		// Delete votes
		$votableUtils = $this->get(VotableUtils::class);
		$votableUtils->deleteVotes($answer, $question, false);

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->deleteActivitiesByAnswer($answer, false);

		// Compute answer counters
		$questionManager = $this->container->get(QuestionManager::class);
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

		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
		$fieldPreprocessorUtils->preprocessFields($comment);

		// Comment counters

		$question->incrementCommentCount();
		$answer->getUser()->getMeta()->incrementCommentCount();

		$om->persist($comment);

		// Create activity
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->createCommentActivity($comment, false);

		// Remove answer

		$this->delete($answer, true);

		// Update index
		if ($question instanceof IndexableInterface) {
			$searchUtils = $this->get(SearchUtils::class);
			$searchUtils->replaceEntityInIndex($question);
		}

	}

}