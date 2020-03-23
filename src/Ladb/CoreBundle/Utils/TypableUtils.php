<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Model\TypableInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;

class TypableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.typable_utils';

	/////

	public static function getStrippedName(TypableInterface $typable, $delimiter = '_', $capitalize = false) {
		return self::getStrippedNameByType($typable->getType(), $delimiter);
	}

	public static function getStrippedNameByType($type, $delimiter = '_', $capitalize = false) {
		$a = null;
		switch ($type) {

			// Comment
			case \Ladb\CoreBundle\Entity\Core\Comment::TYPE:
				$a = array( 'core', 'comment' );
				break;
			// Thread (Message)
			case \Ladb\CoreBundle\Entity\Message\Thread::TYPE:
				$a = array( 'message', 'thread' );
				break;
			// Message (Message)
			case \Ladb\CoreBundle\Entity\Message\Message::TYPE:
				$a = array( 'message', 'message' );
				break;
			// Review
			case \Ladb\CoreBundle\Entity\Core\Review::TYPE:
				$a = array( 'core', 'review' );
				break;
			// Tip
			case \Ladb\CoreBundle\Entity\Core\Tip::TYPE:
				$a = array( 'core', 'tip' );
				break;
			// Feedback
			case \Ladb\CoreBundle\Entity\Core\Feedback::TYPE:
				$a = array( 'core', 'feedback' );
				break;

			// Creation
			case \Ladb\CoreBundle\Entity\Wonder\Creation::TYPE:
				$a = array( 'wonder', 'creation' );
				break;
			// Workshop
			case \Ladb\CoreBundle\Entity\Wonder\Workshop::TYPE:
				$a = array( 'wonder', 'workshop' );
				break;
			// Find
			case \Ladb\CoreBundle\Entity\Find\Find::TYPE:
				$a = array( 'find', 'find' );
				break;
			// Plan
			case \Ladb\CoreBundle\Entity\Wonder\Plan::TYPE:
				$a = array( 'wonder', 'plan' );
				break;
			// Howto
			case \Ladb\CoreBundle\Entity\Howto\Howto::TYPE:
				$a = array( 'howto', 'howto' );
				break;
			// Article
			case \Ladb\CoreBundle\Entity\Howto\Article::TYPE:
				$a = array( 'howto', 'article' );
				break;
			// Post
			case \Ladb\CoreBundle\Entity\Blog\Post::TYPE:
				$a = array( 'blog', 'post' );
				break;
			// Wood
			case \Ladb\CoreBundle\Entity\Knowledge\Wood::TYPE:
				$a = array( 'knowledge', 'wood' );
				break;
			// Question (Faq)
			case \Ladb\CoreBundle\Entity\Faq\Question::TYPE:
				$a = array( 'faq', 'question' );
				break;
			// Provider
			case \Ladb\CoreBundle\Entity\Knowledge\Provider::TYPE:
				$a = array( 'knowledge', 'provider' );
				break;
			// Question (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Question::TYPE:
				$a = array( 'qa', 'question' );
				break;
			// Answer (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Answer::TYPE:
				$a = array( 'qa', 'answer' );
				break;
			// School
			case \Ladb\CoreBundle\Entity\Knowledge\School::TYPE:
				$a = array( 'knowledge', 'school' );
				break;
			// Testimonial (School)
			case \Ladb\CoreBundle\Entity\Knowledge\School\Testimonial::TYPE:
				$a = array( 'knowledge', 'testimonial' );
				break;
			// Graphic
			case \Ladb\CoreBundle\Entity\Promotion\Graphic::TYPE:
				$a = array( 'promotion', 'graphic' );
				break;
			// Workflow
			case \Ladb\CoreBundle\Entity\Workflow\Workflow::TYPE:
				$a = array( 'workflow', 'workflow' );
				break;
			// Book
			case \Ladb\CoreBundle\Entity\Knowledge\Book::TYPE:
				$a = array( 'knowledge', 'book' );
				break;
			// Collection
			case \Ladb\CoreBundle\Entity\Collection\Collection::TYPE:
				$a = array( 'collection', 'collection' );
				break;
			// Software
			case \Ladb\CoreBundle\Entity\Knowledge\Software::TYPE:
				$a = array( 'knowledge', 'software' );
				break;
			// Offer
			case \Ladb\CoreBundle\Entity\Offer\Offer::TYPE:
				$a = array( 'offer', 'offer' );
				break;
			// Event
			case \Ladb\CoreBundle\Entity\Event\Event::TYPE:
				$a = array( 'event', 'event' );
				break;

		}
		if ($a) {
			return implode($delimiter, $capitalize ? array_map('ucfirst', $a) : $a);
		}
		return '';
	}

	public function findTypable($type, $id) {
		$repository = $this->getRepositoryByType($type);
		if (is_null($repository)) {
			throw new \Exception('Unknow Typable - Bad type (type='.$type.').');
		}
		$typable = $repository->findOneByIdJoinedOn($id, $repository->getDefaultJoinOptions());
		if (is_null($typable)) {
			throw new \Exception('Unknow Typable - Bad id (type='.$type.', id='.$id.').');
		}
		return $typable;
	}

	public function findTypables($type, array $ids) {
		$repository = $this->getRepositoryByType($type);
		if (is_null($repository)) {
			throw new \Exception('Unknow Typable - Bad type (type='.$type.').');
		}
		$typables = $repository->findByIdsJoinedOn($ids, $repository->getDefaultJoinOptions());
		if (is_null($typables)) {
			throw new \Exception('Unknow Typable - Bad ids (type='.$type.', ids='.implode(',', $ids).').');
		}
		// Reorder on ids
		$identifierPropertyPath = new PropertyPath('id');
		$propertyAccessor = PropertyAccess::createPropertyAccessor();
		$idPos = array_flip($ids);
		usort($typables, function($a, $b) use ($idPos, $identifierPropertyPath, $propertyAccessor) {
			return $idPos[$propertyAccessor->getValue($a, $identifierPropertyPath)] > $idPos[$propertyAccessor->getValue($b, $identifierPropertyPath)];
		});
		return $typables;
	}

	public function getRepositoryByType($type) {
		$className = $this->getClassNameByType($type);
		if (!is_null($className)) {
			$om = $this->get('doctrine.orm.entity_manager');
			$repository = $om->getRepository($className);
			return $repository;
		}
		return null;
	}

	/////

	public static function getClassNameByType($type) {
		$class = self::getClassByType($type);
		if (!is_null($class)) {
			return $class::CLASS_NAME;
		}
		return null;
	}

	public static function getClassByType($type) {
		switch ($type) {

			// Comment
			case \Ladb\CoreBundle\Entity\Core\Comment::TYPE:
				return '\Ladb\CoreBundle\Entity\Core\Comment';
			// Thread (Message)
			case \Ladb\CoreBundle\Entity\Message\Thread::TYPE:
				return '\Ladb\CoreBundle\Entity\Message\Thread';
			// Message (Message)
			case \Ladb\CoreBundle\Entity\Message\Message::TYPE:
				return '\Ladb\CoreBundle\Entity\Message\Message';
			// Review
			case \Ladb\CoreBundle\Entity\Core\Review::TYPE:
				return '\Ladb\CoreBundle\Entity\Core\Review';
			// Tip
			case \Ladb\CoreBundle\Entity\Core\Tip::TYPE:
				return '\Ladb\CoreBundle\Entity\Core\Tip';
			// Feedback
			case \Ladb\CoreBundle\Entity\Core\Feedback::TYPE:
				return '\Ladb\CoreBundle\Entity\Core\Feedback';

			// Text
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Text::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Text';
			// Integer
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Integer::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Integer';
			// Picture
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Picture::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Picture';
			// Url
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Url::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Url';
			// Location
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Location::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Location';
			// Phone
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Phone::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Phone';
			// Sign
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Sign::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Sign';
			// LongText
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Longtext::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Longtext';
			// Language
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Language::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Language';
			// Isbn
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Isbn::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Isbn';
			// Price
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Price::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Price';
			// SoftwareIdentity
			case \Ladb\CoreBundle\Entity\Knowledge\Value\SoftwareIdentity::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\SoftwareIdentity';
			// FileExtension
			case \Ladb\CoreBundle\Entity\Knowledge\Value\FileExtension::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\FileExtension';
			// LinkableText
			case \Ladb\CoreBundle\Entity\Knowledge\Value\LinkableText::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\LinkableText';
			// Video
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Video::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Value\Video';

			// Creation
			case \Ladb\CoreBundle\Entity\Wonder\Creation::TYPE:
				return '\Ladb\CoreBundle\Entity\Wonder\Creation';
			// Workshop
			case \Ladb\CoreBundle\Entity\Wonder\Workshop::TYPE:
				return '\Ladb\CoreBundle\Entity\Wonder\Workshop';
			// Find
			case \Ladb\CoreBundle\Entity\Find\Find::TYPE:
				return '\Ladb\CoreBundle\Entity\Find\Find';
			// Plan
			case \Ladb\CoreBundle\Entity\Wonder\Plan::TYPE:
				return '\Ladb\CoreBundle\Entity\Wonder\Plan';
			// Howto
			case \Ladb\CoreBundle\Entity\Howto\Howto::TYPE:
				return '\Ladb\CoreBundle\Entity\Howto\Howto';
			// Article
			case \Ladb\CoreBundle\Entity\Howto\Article::TYPE:
				return '\Ladb\CoreBundle\Entity\Howto\Article';
			// Post
			case \Ladb\CoreBundle\Entity\Blog\Post::TYPE:
				return '\Ladb\CoreBundle\Entity\Blog\Post';
			// Wood
			case \Ladb\CoreBundle\Entity\Knowledge\Wood::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Wood';
			// Question (Faq)
			case \Ladb\CoreBundle\Entity\Faq\Question::TYPE:
				return '\Ladb\CoreBundle\Entity\Faq\Question';
			// Provider
			case \Ladb\CoreBundle\Entity\Knowledge\Provider::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Provider';
			// Took
			case \Ladb\CoreBundle\Entity\Youtook\Took::TYPE:
				return '\Ladb\CoreBundle\Entity\Youtook\Took';
			// Question (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Question::TYPE:
				return '\Ladb\CoreBundle\Entity\Qa\Question';
			// Answer (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Answer::TYPE:
				return '\Ladb\CoreBundle\Entity\Qa\Answer';
			// School
			case \Ladb\CoreBundle\Entity\Knowledge\School::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\School';
			// Testimonial (School)
			case \Ladb\CoreBundle\Entity\Knowledge\School\Testimonial::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\School\Testimonial';
			// Graphic (School)
			case \Ladb\CoreBundle\Entity\Promotion\Graphic::TYPE:
				return '\Ladb\CoreBundle\Entity\Promotion\Graphic';
			// Workflow
			case \Ladb\CoreBundle\Entity\Workflow\Workflow::TYPE:
				return '\Ladb\CoreBundle\Entity\Workflow\Workflow';
			// Book
			case \Ladb\CoreBundle\Entity\Knowledge\Book::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Book';
			// Collection
			case \Ladb\CoreBundle\Entity\Collection\Collection::TYPE:
				return '\Ladb\CoreBundle\Entity\Collection\Collection';
			// Software
			case \Ladb\CoreBundle\Entity\Knowledge\Software::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Software';
			// Offer
			case \Ladb\CoreBundle\Entity\Offer\Offer::TYPE:
				return '\Ladb\CoreBundle\Entity\Offer\Offer';
			// Event
			case \Ladb\CoreBundle\Entity\Event\Event::TYPE:
				return '\Ladb\CoreBundle\Entity\Event\Event';

		}
		return null;
	}

	/////

	public function getUrlAction(TypableInterface $typable, $action = 'show', $absoluteUrl = true, $useSluggedId = true, $addionalParams = null) {
		$router = $this->get('router');
		$url = '';
		$referenceType = $absoluteUrl ? \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL : \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH;
		$useSluggedId = $useSluggedId && method_exists($typable, 'getSluggedId');
		if ($action == 'list') {
			$params = array();
		} else {
			$params = array( 'id' => $useSluggedId ? $typable->getSluggedId() : $typable->getId() );
		}
		if (!is_null($addionalParams) && is_array($addionalParams)) {
			$params = array_merge($params, $addionalParams);
		}
		switch ($typable->getType()) {

			case \Ladb\CoreBundle\Entity\Core\Comment::TYPE:
				$url = $router->generate('core_comment_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Message\Thread::TYPE:
				$url = $router->generate('core_message_thread_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Message\Message::TYPE:
				$params['id'] = $typable->getThread()->getId();
				$url = $router->generate('core_message_thread_'.$action, $params, $referenceType).'#_message_'.$typable->getId();
				break;
			case \Ladb\CoreBundle\Entity\Core\Review::TYPE:
				$url = $router->generate('core_review_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Core\Tip::TYPE:
				$url = $router->generate('core_tip_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Core\Feedback::TYPE:
				$url = $router->generate('core_feedback_'.$action, $params, $referenceType);
				break;

			case \Ladb\CoreBundle\Entity\Knowledge\Value\Text::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Integer::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Picture::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Url::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Location::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Phone::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Sign::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Longtext::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Language::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Isbn::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Price::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\SoftwareIdentity::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\FileExtension::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\LinkableText::TYPE:
			case \Ladb\CoreBundle\Entity\Knowledge\Value\Video::TYPE:
				$parentTypable = $this->findTypable($typable->getParentEntityType(), $typable->getParentEntityId());
				return $this->getUrlAction($parentTypable, $action, $absoluteUrl, $useSluggedId, $addionalParams);

			case \Ladb\CoreBundle\Entity\Wonder\Creation::TYPE:
				$url = $router->generate('core_creation_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Wonder\Workshop::TYPE:
				$url = $router->generate('core_workshop_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Find\Find::TYPE:
				$url = $router->generate('core_find_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Wonder\Plan::TYPE:
				$url = $router->generate('core_plan_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Howto\Howto::TYPE:
				$url = $router->generate('core_howto_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Howto\Article::TYPE:
				$url = $router->generate('core_howto_article_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Blog\Post::TYPE:
				$url = $router->generate('core_blog_post_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Knowledge\Wood::TYPE:
				$url = $router->generate('core_wood_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Faq\Question::TYPE:
				$url = $router->generate('core_faq_question_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Knowledge\Provider::TYPE:
				$url = $router->generate('core_provider_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Youtook\Took::TYPE:
				$url = $router->generate('core_youtook_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Qa\Question::TYPE:
				$url = $router->generate('core_qa_question_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Qa\Answer::TYPE:
				$url = $router->generate('core_qa_answer_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Knowledge\School::TYPE:
				$url = $router->generate('core_school_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Knowledge\School\Testimonial::TYPE:
				$url = $router->generate('core_knowledge_school_testimonial_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Promotion\Graphic::TYPE:
				$url = $router->generate('core_promotion_graphic_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Workflow\Workflow::TYPE:
				$url = $router->generate('core_workflow_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Knowledge\Book::TYPE:
				$url = $router->generate('core_book_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Collection\Collection::TYPE:
				$url = $router->generate('core_collection_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Knowledge\Software::TYPE:
				$url = $router->generate('core_software_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Offer\Offer::TYPE:
				$url = $router->generate('core_offer_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Event\Event::TYPE:
				$url = $router->generate('core_event_'.$action, $params, $referenceType);
				break;

		}
		return $url;
	}

	/////

	public function getIcon(TypableInterface $typable) {
		return $this->getIconByType($typable->getType());
	}

	public function getIconByType($type) {
		switch ($type) {

			// Comment
			case \Ladb\CoreBundle\Entity\Core\Comment::TYPE:
				return 'comment';
			// Message (Message)
			case \Ladb\CoreBundle\Entity\Message\Message::TYPE:
				return 'message';
			// Review
			case \Ladb\CoreBundle\Entity\Core\Review::TYPE:
				return 'review';
			// Tip
			case \Ladb\CoreBundle\Entity\Core\Tip::TYPE:
				return 'tip';
			// Feedback
			case \Ladb\CoreBundle\Entity\Core\Feedback::TYPE:
				return 'feedback';

			// Creation
			case \Ladb\CoreBundle\Entity\Wonder\Creation::TYPE:
				return 'creation';
			// Workshop
			case \Ladb\CoreBundle\Entity\Wonder\Workshop::TYPE:
				return 'workshop';
			// Find
			case \Ladb\CoreBundle\Entity\Find\Find::TYPE:
				return 'find';
			// Plan
			case \Ladb\CoreBundle\Entity\Wonder\Plan::TYPE:
				return 'plan';
			// Howto
			case \Ladb\CoreBundle\Entity\Howto\Howto::TYPE:
				return 'howto';
			// Post
			case \Ladb\CoreBundle\Entity\Blog\Post::TYPE:
				return 'blog';
			// Wood
			case \Ladb\CoreBundle\Entity\Knowledge\Wood::TYPE:
				return 'wood';
			// Question (Faq)
			case \Ladb\CoreBundle\Entity\Faq\Question::TYPE:
				return 'help';
			// Provider
			case \Ladb\CoreBundle\Entity\Knowledge\Provider::TYPE:
				return 'provider';
			// Question (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Question::TYPE:
				return 'question';
			// Answer (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Answer::TYPE:
				return 'answer';
			// School
			case \Ladb\CoreBundle\Entity\Knowledge\School::TYPE:
				return 'school';
			// Testimonial (School)
			case \Ladb\CoreBundle\Entity\Knowledge\School\Testimonial::TYPE:
				return 'review';
			// Graphic (School)
			case \Ladb\CoreBundle\Entity\Promotion\Graphic::TYPE:
				return 'graphic';
			// Workflow
			case \Ladb\CoreBundle\Entity\Workflow\Workflow::TYPE:
				return 'workflow';
			// Book
			case \Ladb\CoreBundle\Entity\Knowledge\Book::TYPE:
				return 'book';
			// Collection
			case \Ladb\CoreBundle\Entity\Collection\Collection::TYPE:
				return 'collection';
			// Software
			case \Ladb\CoreBundle\Entity\Knowledge\Software::TYPE:
				return 'software';
			// Offer
			case \Ladb\CoreBundle\Entity\Offer\Offer::TYPE:
				return 'offer';
			// Event
			case \Ladb\CoreBundle\Entity\Event\Event::TYPE:
				return 'event';

		}
		return null;
	}

}