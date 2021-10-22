<?php

namespace App\Manager\Core;

use App\Entity\Core\Comment;
use App\Entity\Qa\Answer;
use App\Entity\Qa\Question;
use App\Manager\AbstractManager;
use App\Utils\BlockBodiedUtils;
use App\Utils\CommentableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\SearchUtils;

class CommentManager extends AbstractManager {

	const NAME = 'ladb_core.core_comment_manager';

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), array(
            BlockBodiedUtils::class => '?'.BlockBodiedUtils::class,
            CommentableUtils::class => '?'.CommentableUtils::class,
            FieldPreprocessorUtils::class => '?'.FieldPreprocessorUtils::class,
            SearchUtils::class => '?'.SearchUtils::class,

        ));
    }

	public function delete(Comment $comment, $flush = true) {
		parent::deleteEntity($comment, $flush);
	}

	public function convertToAnswer(Comment $comment, Question $question) {
		$om = $this->getDoctrine()->getManager();

		$answer = new Answer();
		$answer->setCreatedAt($comment->getCreatedAt());
		$answer->setUpdatedAt($comment->getUpdatedAt());
		$answer->setUser($comment->getUser());
		$answer->setParentEntity($question);
		$answer->setParentEntityField('bestAnswer');

		$blockBodiedUtils = $this->get(BlockBodiedUtils::class);
		$blockBodiedUtils->copyBodyTo($comment, $answer);
		$blockBodiedUtils->copyPicturesTo($comment, $answer);

		$question->addAnswer($answer);

		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
		$fieldPreprocessorUtils->preprocessBodyBlocksField($answer);

		// Answer counters

		$question->incrementAnswerCount();
		$answer->getUser()->getMeta()->incrementAnswerCount();

		// Persist answer to generate ID
		$om->persist($answer);
		$om->flush();

		// Transfer children comments
		$commentableUtils = $this->get(CommentableUtils::class);
		$commentableUtils->transferChildrenComments($comment, $question, $answer);

		// Remove comment

		$commentableUtils->deleteComment($comment, $question, $om, true);

		// Update index
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->replaceEntityInIndex($question);

		return $answer;
	}

}