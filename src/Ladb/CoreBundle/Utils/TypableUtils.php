<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Model\TypableInterface;

class TypableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.typable_utils';

	/////

	public static function getStrippedName(TypableInterface $typable, $long = false) {
		return self::getStrippedNameByType($typable->getType(), $long);
	}

	public static function getStrippedNameByType($type, $long = false) {
		switch ($type) {

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
			// Article
			case \Ladb\CoreBundle\Entity\Howto\Article::TYPE:
				return $long ? 'howto.article' : 'article';
			// Post
			case \Ladb\CoreBundle\Entity\Blog\Post::TYPE:
				return $long ? 'blog.post' : 'post';
			// Wood
			case \Ladb\CoreBundle\Entity\Knowledge\Wood::TYPE:
				return $long ? 'knowledge.wood': 'wood';
			// Question
			case \Ladb\CoreBundle\Entity\Faq\Question::TYPE:
				return $long ? 'faq.question' : 'question';
			// Provider
			case \Ladb\CoreBundle\Entity\Knowledge\Provider::TYPE:
				return $long ? 'knowledge.provider': 'provider';
			// Question (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Question::TYPE:
				return $long ? 'qa.question': 'question';
			// Answer (Qa)
			case \Ladb\CoreBundle\Entity\Qa\Answer::TYPE:
				return $long ? 'qa.answer': 'answer';

		}
		return '';
	}

	public function findTypable($type, $id) {
		$repository = $this->getRepositoryByType($type);
		if (is_null($repository)) {
			throw new \Exception('Unknow Typable - Bad type (type='.$type.', id='.$id.').');
		}
		$typable = $repository->findOneById($id);
		if (is_null($typable)) {
			throw new \Exception('Unknow Typable - Bad id (type='.$type.', id='.$id.').');
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
			case \Ladb\CoreBundle\Entity\Comment::TYPE:
				return '\Ladb\CoreBundle\Entity\Comment';

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
			// Question
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
		}
		return $url;
	}

}