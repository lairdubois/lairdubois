<?php

namespace Ladb\CoreBundle\Manager\Core;

use Ladb\CoreBundle\Entity\Core\Block\Gallery;
use Ladb\CoreBundle\Entity\Core\Block\Text;
use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Entity\Qa\Answer;
use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\AbstractManager;
use Ladb\CoreBundle\Manager\Qa\QuestionManager;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\MentionUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\VotableUtils;

class CommentManager extends AbstractManager {

	const NAME = 'ladb_core.comment_manager';

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

		$blockBodiedUtils = $this->get(BlockBodiedUtils::NAME);
		$blockBodiedUtils->copyBodyTo($comment, $answer);
		$blockBodiedUtils->copyPicturesTo($comment, $answer);

		$question->addAnswer($answer);

		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
		$fieldPreprocessorUtils->preprocessBodyBlocksField($answer);

		// Answer counters

		$question->incrementAnswerCount();
		$answer->getUser()->getMeta()->incrementAnswerCount();

		// Persist answer to generate ID
		$om->persist($answer);
		$om->flush();

		// Transfer children comments
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$commentableUtils->transferChildrenComments($comment, $question, $answer);

		// Remove comment

		$commentableUtils->deleteComment($comment, $question, $om, true);

		// Update index
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($question);

		return $answer;
	}

}