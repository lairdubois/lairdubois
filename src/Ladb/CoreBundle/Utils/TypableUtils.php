<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Model\TypableInterface;

class TypableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.typable_utils';

	/////

	public static function getStrippedName(TypableInterface $typable, $delimiter = '_') {
		return self::getStrippedNameByType($typable->getType(), $delimiter);
	}

	public static function getStrippedNameByType($type, $delimiter = '_') {
		switch ($type) {

			// Comment
			case \Ladb\CoreBundle\Entity\Core\Comment::TYPE:
				return implode($delimiter, array( 'core', 'comment' ));
			// Thread (Message)
			case \Ladb\CoreBundle\Entity\Message\Thread::TYPE:
				return implode($delimiter, array( 'message', 'thread' ));
			// Message (Message)
			case \Ladb\CoreBundle\Entity\Message\Message::TYPE:
				return implode($delimiter, array( 'message', 'message' ));

			// Creation
			case \Ladb\CoreBundle\Entity\Wonder\Creation::TYPE:
				return implode($delimiter, array( 'wonder', 'creation' ));
			// Workshop
			case \Ladb\CoreBundle\Entity\Wonder\Workshop::TYPE:
				return implode($delimiter, array( 'wonder', 'workshop' ));
			// Find
			case \Ladb\CoreBundle\Entity\Find\Find::TYPE:
				return implode($delimiter, array( 'find', 'find' ));
			// Plan
			case \Ladb\CoreBundle\Entity\Wonder\Plan::TYPE:
				return implode($delimiter, array( 'wonder', 'plan' ));
			// Howto
			case \Ladb\CoreBundle\Entity\Howto\Howto::TYPE:
				return implode($delimiter, array( 'howto', 'howto' ));
			// Article
			case \Ladb\CoreBundle\Entity\Howto\Article::TYPE:
				return implode($delimiter, array( 'howto', 'article' ));
			// Post
			case \Ladb\CoreBundle\Entity\Blog\Post::TYPE:
				return implode($delimiter, array( 'blog', 'post' ));
			// Wood
			case \Ladb\CoreBundle\Entity\Knowledge\Wood::TYPE:
				return implode($delimiter, array( 'knowledge', 'wood' ));
			// Question (Faq)
			case \Ladb\CoreBundle\Entity\Faq\Question::TYPE:
				return implode($delimiter, array( 'faq', 'question' ));
			// Provider
			case \Ladb\CoreBundle\Entity\Knowledge\Provider::TYPE:
				return implode($delimiter, array( 'knowledge', 'provider' ));
			// Question (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Question::TYPE:
				return implode($delimiter, array( 'qa', 'question' ));
			// Answer (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Answer::TYPE:
				return implode($delimiter, array( 'qa', 'answer' ));
			// School
			case \Ladb\CoreBundle\Entity\Knowledge\School::TYPE:
				return implode($delimiter, array( 'knowledge', 'school' ));
			// Testimonial (School)
			case \Ladb\CoreBundle\Entity\Knowledge\School\Testimonial::TYPE:
				return implode($delimiter, array( 'knowledge', 'testimonial' ));
			// Graphic
			case \Ladb\CoreBundle\Entity\Promotion\Graphic::TYPE:
				return implode($delimiter, array( 'promotion', 'graphic' ));
			// Workflow
			case \Ladb\CoreBundle\Entity\Workflow\Workflow::TYPE:
				return implode($delimiter, array( 'workflow', 'workflow' ));
			// Book
			case \Ladb\CoreBundle\Entity\Knowledge\Book::TYPE:
				return implode($delimiter, array( 'knowledge', 'book' ));
			// Review (Book)
			case \Ladb\CoreBundle\Entity\Knowledge\Book\Review::TYPE:
				return implode($delimiter, array( 'knowledge', 'review' ));

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
		$typable = $repository->findByIdsJoinedOn($ids, $repository->getDefaultJoinOptions());
		if (is_null($typable)) {
			throw new \Exception('Unknow Typable - Bad id (type='.$type.', ids='.implode(',', $ids).').');
		}
		return $typable;
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
			// Review (Book)
			case \Ladb\CoreBundle\Entity\Knowledge\Book\Review::TYPE:
				return '\Ladb\CoreBundle\Entity\Knowledge\Book\Review';

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

			case \Ladb\CoreBundle\Entity\Message\Thread::TYPE:
				$url = $router->generate('core_message_thread_'.$action, $params, $referenceType);
				break;
			case \Ladb\CoreBundle\Entity\Message\Message::TYPE:
				$params['id'] = $typable->getThread()->getId();
				$url = $router->generate('core_message_thread_'.$action, $params, $referenceType).'#_message_'.$typable->getId();
				break;

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
			case \Ladb\CoreBundle\Entity\Knowledge\School::TYPE:
				$url = $router->generate('core_school_'.$action, $params, $referenceType);
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

		}
		return $url;
	}

}