<?php

namespace App\Utils;

use App\Model\TypableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;

class TypableUtils extends AbstractContainerAwareUtils {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            'doctrine.orm.entity_manager' => '?'.EntityManagerInterface::class,
        ));
    }

    public static function getStrippedName(TypableInterface $typable, $delimiter = '_', $capitalize = false) {
		return self::getStrippedNameByType($typable->getType(), $delimiter, $capitalize);
	}

    public static function getStrippedNameByType($type, $delimiter = '_', $capitalize = false) {
        $a = null;
        switch ($type) {

            // Comment
            case \App\Entity\Core\Comment::TYPE:
                $a = array( 'core', 'comment' );
                break;
            // Thread (Message)
            case \App\Entity\Message\Thread::TYPE:
                $a = array( 'message', 'thread' );
                break;
            // Message (Message)
            case \App\Entity\Message\Message::TYPE:
                $a = array( 'message', 'message' );
                break;
            // Review
            case \App\Entity\Core\Review::TYPE:
                $a = array( 'core', 'review' );
                break;
            // Tip
            case \App\Entity\Core\Tip::TYPE:
                $a = array( 'core', 'tip' );
                break;
            // Feedback
            case \App\Entity\Core\Feedback::TYPE:
                $a = array( 'core', 'feedback' );
                break;

            // Creation
            case \App\Entity\Wonder\Creation::TYPE:
                $a = array( 'wonder', 'creation' );
                break;
            // Workshop
            case \App\Entity\Wonder\Workshop::TYPE:
                $a = array( 'wonder', 'workshop' );
                break;
            // Find
            case \App\Entity\Find\Find::TYPE:
                $a = array( 'find', 'find' );
                break;
            // Plan
            case \App\Entity\Wonder\Plan::TYPE:
                $a = array( 'wonder', 'plan' );
                break;
            // Howto
            case \App\Entity\Howto\Howto::TYPE:
                $a = array( 'howto', 'howto' );
                break;
            // Article
            case \App\Entity\Howto\Article::TYPE:
                $a = array( 'howto', 'article' );
                break;
            // Post
            case \App\Entity\Blog\Post::TYPE:
                $a = array( 'blog', 'post' );
                break;
            // Wood
            case \App\Entity\Knowledge\Wood::TYPE:
                $a = array( 'knowledge', 'wood' );
                break;
            // Question (Faq)
            case \App\Entity\Faq\Question::TYPE:
                $a = array( 'faq', 'question' );
                break;
            // Provider
            case \App\Entity\Knowledge\Provider::TYPE:
                $a = array( 'knowledge', 'provider' );
                break;
            // Question (Qa)
            case \App\Entity\Qa\Question::TYPE:
                $a = array( 'qa', 'question' );
                break;
            // Answer (Qa)
            case \App\Entity\Qa\Answer::TYPE:
                $a = array( 'qa', 'answer' );
                break;
            // School
            case \App\Entity\Knowledge\School::TYPE:
                $a = array( 'knowledge', 'school' );
                break;
            // Testimonial (School)
            case \App\Entity\Knowledge\School\Testimonial::TYPE:
                $a = array( 'knowledge', 'testimonial' );
                break;
            // Graphic
            case \App\Entity\Promotion\Graphic::TYPE:
                $a = array( 'promotion', 'graphic' );
                break;
            // Workflow
            case \App\Entity\Workflow\Workflow::TYPE:
                $a = array( 'workflow', 'workflow' );
                break;
            // Book
            case \App\Entity\Knowledge\Book::TYPE:
                $a = array( 'knowledge', 'book' );
                break;
            // Collection
            case \App\Entity\Collection\Collection::TYPE:
                $a = array( 'collection', 'collection' );
                break;
            // Software
            case \App\Entity\Knowledge\Software::TYPE:
                $a = array( 'knowledge', 'software' );
                break;
            // Offer
            case \App\Entity\Offer\Offer::TYPE:
                $a = array( 'offer', 'offer' );
                break;
            // Event
            case \App\Entity\Event\Event::TYPE:
                $a = array( 'event', 'event' );
                break;
            // Tool
            case \App\Entity\Knowledge\Tool::TYPE:
                $a = array( 'knowledge', 'tool' );
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
        $className = $this->getClassByType($type);
        if (!is_null($className)) {
            $om = $this->get('doctrine.orm.entity_manager');
            $repository = $om->getRepository($className);
            return $repository;
        }
        return null;
    }

    /////

    public static function getClassByType($type) {
        switch ($type) {

            // Comment
            case \App\Entity\Core\Comment::TYPE:
                return '\App\Entity\Core\Comment';
            // Thread (Message)
            case \App\Entity\Message\Thread::TYPE:
                return '\App\Entity\Message\Thread';
            // Message (Message)
            case \App\Entity\Message\Message::TYPE:
                return '\App\Entity\Message\Message';
            // Review
            case \App\Entity\Core\Review::TYPE:
                return '\App\Entity\Core\Review';
            // Tip
            case \App\Entity\Core\Tip::TYPE:
                return '\App\Entity\Core\Tip';
            // Feedback
            case \App\Entity\Core\Feedback::TYPE:
                return '\App\Entity\Core\Feedback';

            // Text
            case \App\Entity\Knowledge\Value\Text::TYPE:
                return '\App\Entity\Knowledge\Value\Text';
            // Integer
            case \App\Entity\Knowledge\Value\Integer::TYPE:
                return '\App\Entity\Knowledge\Value\Integer';
            // Picture
            case \App\Entity\Knowledge\Value\Picture::TYPE:
                return '\App\Entity\Knowledge\Value\Picture';
            // Url
            case \App\Entity\Knowledge\Value\Url::TYPE:
                return '\App\Entity\Knowledge\Value\Url';
            // Location
            case \App\Entity\Knowledge\Value\Location::TYPE:
                return '\App\Entity\Knowledge\Value\Location';
            // Phone
            case \App\Entity\Knowledge\Value\Phone::TYPE:
                return '\App\Entity\Knowledge\Value\Phone';
            // Sign
            case \App\Entity\Knowledge\Value\Sign::TYPE:
                return '\App\Entity\Knowledge\Value\Sign';
            // LongText
            case \App\Entity\Knowledge\Value\Longtext::TYPE:
                return '\App\Entity\Knowledge\Value\Longtext';
            // Language
            case \App\Entity\Knowledge\Value\Language::TYPE:
                return '\App\Entity\Knowledge\Value\Language';
            // Isbn
            case \App\Entity\Knowledge\Value\Isbn::TYPE:
                return '\App\Entity\Knowledge\Value\Isbn';
            // Price
            case \App\Entity\Knowledge\Value\Price::TYPE:
                return '\App\Entity\Knowledge\Value\Price';
            // SoftwareIdentity
            case \App\Entity\Knowledge\Value\SoftwareIdentity::TYPE:
                return '\App\Entity\Knowledge\Value\SoftwareIdentity';
            // FileExtension
            case \App\Entity\Knowledge\Value\FileExtension::TYPE:
                return '\App\Entity\Knowledge\Value\FileExtension';
            // LinkableText
            case \App\Entity\Knowledge\Value\LinkableText::TYPE:
                return '\App\Entity\Knowledge\Value\LinkableText';
            // Video
            case \App\Entity\Knowledge\Value\Video::TYPE:
                return '\App\Entity\Knowledge\Value\Video';
            // BookIdentity
            case \App\Entity\Knowledge\Value\BookIdentity::TYPE:
                return '\App\Entity\Knowledge\Value\BookIdentity';
            // Pdf
            case \App\Entity\Knowledge\Value\Pdf::TYPE:
                return '\App\Entity\Knowledge\Value\Pdf';
            // Decimal
            case \App\Entity\Knowledge\Value\Decimal::TYPE:
                return '\App\Entity\Knowledge\Value\Decimal';

            // Creation
            case \App\Entity\Wonder\Creation::TYPE:
                return '\App\Entity\Wonder\Creation';
            // Workshop
            case \App\Entity\Wonder\Workshop::TYPE:
                return '\App\Entity\Wonder\Workshop';
            // Find
            case \App\Entity\Find\Find::TYPE:
                return '\App\Entity\Find\Find';
            // Plan
            case \App\Entity\Wonder\Plan::TYPE:
                return '\App\Entity\Wonder\Plan';
            // Howto
            case \App\Entity\Howto\Howto::TYPE:
                return '\App\Entity\Howto\Howto';
            // Article
            case \App\Entity\Howto\Article::TYPE:
                return '\App\Entity\Howto\Article';
            // Post
            case \App\Entity\Blog\Post::TYPE:
                return '\App\Entity\Blog\Post';
            // Wood
            case \App\Entity\Knowledge\Wood::TYPE:
                return '\App\Entity\Knowledge\Wood';
            // Question (Faq)
            case \App\Entity\Faq\Question::TYPE:
                return '\App\Entity\Faq\Question';
            // Provider
            case \App\Entity\Knowledge\Provider::TYPE:
                return '\App\Entity\Knowledge\Provider';
            // Took
            case \App\Entity\Youtook\Took::TYPE:
                return '\App\Entity\Youtook\Took';
            // Question (Qa)
            case \App\Entity\Qa\Question::TYPE:
                return '\App\Entity\Qa\Question';
            // Answer (Qa)
            case \App\Entity\Qa\Answer::TYPE:
                return '\App\Entity\Qa\Answer';
            // School
            case \App\Entity\Knowledge\School::TYPE:
                return '\App\Entity\Knowledge\School';
            // Testimonial (School)
            case \App\Entity\Knowledge\School\Testimonial::TYPE:
                return '\App\Entity\Knowledge\School\Testimonial';
            // Graphic (School)
            case \App\Entity\Promotion\Graphic::TYPE:
                return '\App\Entity\Promotion\Graphic';
            // Workflow
            case \App\Entity\Workflow\Workflow::TYPE:
                return '\App\Entity\Workflow\Workflow';
            // Book
            case \App\Entity\Knowledge\Book::TYPE:
                return '\App\Entity\Knowledge\Book';
            // Collection
            case \App\Entity\Collection\Collection::TYPE:
                return '\App\Entity\Collection\Collection';
            // Software
            case \App\Entity\Knowledge\Software::TYPE:
                return '\App\Entity\Knowledge\Software';
            // Offer
            case \App\Entity\Offer\Offer::TYPE:
                return '\App\Entity\Offer\Offer';
            // Event
            case \App\Entity\Event\Event::TYPE:
                return '\App\Entity\Event\Event';
            // Tool
            case \App\Entity\Knowledge\Tool::TYPE:
                return '\App\Entity\Knowledge\Tool';

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

            case \App\Entity\Core\Comment::TYPE:
                $url = $router->generate('core_comment_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Message\Thread::TYPE:
                $url = $router->generate('core_message_thread_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Message\Message::TYPE:
                $params['id'] = $typable->getThread()->getId();
                $url = $router->generate('core_message_thread_'.$action, $params, $referenceType).'#_message_'.$typable->getId();
                break;
            case \App\Entity\Core\Review::TYPE:
                $url = $router->generate('core_review_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Core\Tip::TYPE:
                $url = $router->generate('core_tip_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Core\Feedback::TYPE:
                $url = $router->generate('core_feedback_'.$action, $params, $referenceType);
                break;

            case \App\Entity\Knowledge\Value\Text::TYPE:
            case \App\Entity\Knowledge\Value\Integer::TYPE:
            case \App\Entity\Knowledge\Value\Picture::TYPE:
            case \App\Entity\Knowledge\Value\Url::TYPE:
            case \App\Entity\Knowledge\Value\Location::TYPE:
            case \App\Entity\Knowledge\Value\Phone::TYPE:
            case \App\Entity\Knowledge\Value\Sign::TYPE:
            case \App\Entity\Knowledge\Value\Longtext::TYPE:
            case \App\Entity\Knowledge\Value\Language::TYPE:
            case \App\Entity\Knowledge\Value\Isbn::TYPE:
            case \App\Entity\Knowledge\Value\Price::TYPE:
            case \App\Entity\Knowledge\Value\SoftwareIdentity::TYPE:
            case \App\Entity\Knowledge\Value\FileExtension::TYPE:
            case \App\Entity\Knowledge\Value\LinkableText::TYPE:
            case \App\Entity\Knowledge\Value\Video::TYPE:
            case \App\Entity\Knowledge\Value\BookIdentity::TYPE:
            case \App\Entity\Knowledge\Value\Pdf::TYPE:
            case \App\Entity\Knowledge\Value\Decimal::TYPE:
                $url = $router->generate('core_knowledge_value_'.$action, $params, $referenceType);
                break;

            case \App\Entity\Wonder\Creation::TYPE:
                $url = $router->generate('core_creation_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Wonder\Workshop::TYPE:
                $url = $router->generate('core_workshop_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Find\Find::TYPE:
                $url = $router->generate('core_find_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Wonder\Plan::TYPE:
                $url = $router->generate('core_plan_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Howto\Howto::TYPE:
                $url = $router->generate('core_howto_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Howto\Article::TYPE:
                $url = $router->generate('core_howto_article_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Blog\Post::TYPE:
                $url = $router->generate('core_blog_post_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Knowledge\Wood::TYPE:
                $url = $router->generate('core_wood_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Faq\Question::TYPE:
                $url = $router->generate('core_faq_question_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Knowledge\Provider::TYPE:
                $url = $router->generate('core_provider_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Youtook\Took::TYPE:
                $url = $router->generate('core_youtook_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Qa\Question::TYPE:
                $url = $router->generate('core_qa_question_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Qa\Answer::TYPE:
                $url = $router->generate('core_qa_answer_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Knowledge\School::TYPE:
                $url = $router->generate('core_school_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Knowledge\School\Testimonial::TYPE:
                $url = $router->generate('core_knowledge_school_testimonial_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Promotion\Graphic::TYPE:
                $url = $router->generate('core_promotion_graphic_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Workflow\Workflow::TYPE:
                $url = $router->generate('core_workflow_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Knowledge\Book::TYPE:
                $url = $router->generate('core_book_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Collection\Collection::TYPE:
                $url = $router->generate('core_collection_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Knowledge\Software::TYPE:
                $url = $router->generate('core_software_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Offer\Offer::TYPE:
                $url = $router->generate('core_offer_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Event\Event::TYPE:
                $url = $router->generate('core_event_'.$action, $params, $referenceType);
                break;
            case \App\Entity\Knowledge\Tool::TYPE:
                $url = $router->generate('core_tool_'.$action, $params, $referenceType);
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
            case \App\Entity\Core\Comment::TYPE:
                return 'comment';
            // Message (Message)
            case \App\Entity\Message\Message::TYPE:
                return 'message';
            // Review
            case \App\Entity\Core\Review::TYPE:
                return 'review';
            // Tip
            case \App\Entity\Core\Tip::TYPE:
                return 'tip';
            // Feedback
            case \App\Entity\Core\Feedback::TYPE:
                return 'feedback';

            // Creation
            case \App\Entity\Wonder\Creation::TYPE:
                return 'creation';
            // Workshop
            case \App\Entity\Wonder\Workshop::TYPE:
                return 'workshop';
            // Find
            case \App\Entity\Find\Find::TYPE:
                return 'find';
            // Plan
            case \App\Entity\Wonder\Plan::TYPE:
                return 'plan';
            // Howto
            case \App\Entity\Howto\Howto::TYPE:
                return 'howto';
            // Post
            case \App\Entity\Blog\Post::TYPE:
                return 'blog';
            // Wood
            case \App\Entity\Knowledge\Wood::TYPE:
                return 'wood';
            // Question (Faq)
            case \App\Entity\Faq\Question::TYPE:
                return 'help';
            // Provider
            case \App\Entity\Knowledge\Provider::TYPE:
                return 'provider';
            // Question (Qa)
            case \App\Entity\Qa\Question::TYPE:
                return 'question';
            // Answer (Qa)
            case \App\Entity\Qa\Answer::TYPE:
                return 'answer';
            // School
            case \App\Entity\Knowledge\School::TYPE:
                return 'school';
            // Testimonial (School)
            case \App\Entity\Knowledge\School\Testimonial::TYPE:
                return 'review';
            // Graphic (School)
            case \App\Entity\Promotion\Graphic::TYPE:
                return 'graphic';
            // Workflow
            case \App\Entity\Workflow\Workflow::TYPE:
                return 'workflow';
            // Book
            case \App\Entity\Knowledge\Book::TYPE:
                return 'book';
            // Collection
            case \App\Entity\Collection\Collection::TYPE:
                return 'collection';
            // Software
            case \App\Entity\Knowledge\Software::TYPE:
                return 'software';
            // Offer
            case \App\Entity\Offer\Offer::TYPE:
                return 'offer';
            // Event
            case \App\Entity\Event\Event::TYPE:
                return 'event';
            // Event
            case \App\Entity\Knowledge\Tool::TYPE:
                return 'tool';

        }
        return null;
    }

}