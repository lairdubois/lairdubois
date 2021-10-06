<?php

namespace App\Manager;

use App\Manager\Core\WitnessManager;
use App\Manager\Qa\QuestionManager;
use App\Utils\ActivityUtils;
use App\Utils\BlockBodiedUtils;
use App\Utils\CollectionnableUtils;
use App\Utils\CommentableUtils;
use App\Utils\FeedbackableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\JoinableUtils;
use App\Utils\LikableUtils;
use App\Utils\MentionUtils;
use App\Utils\ReportableUtils;
use App\Utils\SearchUtils;
use App\Utils\TypableUtils;
use App\Utils\ViewableUtils;
use App\Utils\VotableUtils;
use App\Utils\WatchableUtils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractManager implements ServiceSubscriberInterface {

	protected $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

    public static function getSubscribedServices()
    {
        return array(
            'doctrine' => '?'.ManagerRegistry::class,
            'event_dispatcher' => '?'.EventDispatcherInterface::class,
            ActivityUtils::class => '?'.ActivityUtils::class,
            BlockBodiedUtils::class => '?'.BlockBodiedUtils::class,
            CollectionnableUtils::class => '?'.CollectionnableUtils::class,
            CommentableUtils::class => '?'.CommentableUtils::class,
            FeedbackableUtils::class => '?'.FeedbackableUtils::class,
            FieldPreprocessorUtils::class => '?'.FieldPreprocessorUtils::class,
            JoinableUtils::class => '?'.JoinableUtils::class,
            LikableUtils::class => '?'.LikableUtils::class,
            MentionUtils::class => '?'.MentionUtils::class,
            QuestionManager::class => '?'.QuestionManager::class,
            ReportableUtils::class => '?'.ReportableUtils::class,
            SearchUtils::class => '?'.SearchUtils::class,
            TypableUtils::class => '?'.TypableUtils::class,
            ViewableUtils::class => '?'.ViewableUtils::class,
            VotableUtils::class => '?'.VotableUtils::class,
            WatchableUtils::class => '?'.WatchableUtils::class,
            WitnessManager::class => '?'.WitnessManager::class,
        );
    }

	/////

	public function get($id) {
		return $this->container->get($id);
	}

	public function getDoctrine() {
		if (!$this->container->has('doctrine')) {
			throw new \LogicException('The DoctrineBundle is not registered in your application.');
		}
		return $this->container->get('doctrine');
	}

	/////

	public function deleteEntity($entity, $flush = true) {
		$om = $this->getDoctrine()->getManager();

		$om->remove($entity);

		if ($flush) {
			$om->flush();
		}

	}

}