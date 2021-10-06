<?php

namespace App\Controller;

use App\Fos\UserManager;
use App\Manager\Core\WitnessManager;
use App\Manager\Qa\QuestionManager;
use App\Utils\ActivityUtils;
use App\Utils\BlockBodiedUtils;
use App\Utils\CollectionnableUtils;
use App\Utils\CommentableUtils;
use App\Utils\FeedbackableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\GlobalUtils;
use App\Utils\JoinableUtils;
use App\Utils\LikableUtils;
use App\Utils\MaybeUtils;
use App\Utils\MentionUtils;
use App\Utils\ReportableUtils;
use App\Utils\SearchUtils;
use App\Utils\TagUtils;
use App\Utils\TypableUtils;
use App\Utils\ViewableUtils;
use App\Utils\VotableUtils;
use App\Utils\WatchableUtils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\MemcachedStore;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractController extends BaseController {

	const LOCK_TTL_CREATE_ACTION = 3;		// 3 seconds

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), array(
            'doctrine' => '?'.ManagerRegistry::class,
            'event_dispatcher' => '?'.EventDispatcherInterface::class,
            'translator' => TranslatorInterface::class,
            ActivityUtils::class => '?'.ActivityUtils::class,
            BlockBodiedUtils::class => '?'.BlockBodiedUtils::class,
            CollectionnableUtils::class => '?'.CollectionnableUtils::class,
            CommentableUtils::class => '?'.CommentableUtils::class,
            FeedbackableUtils::class => '?'.FeedbackableUtils::class,
            FieldPreprocessorUtils::class => '?'.FieldPreprocessorUtils::class,
            GlobalUtils::class => '?'.GlobalUtils::class,
            JoinableUtils::class => '?'.JoinableUtils::class,
            LikableUtils::class => '?'.LikableUtils::class,
            MaybeUtils::class => '?'.MaybeUtils::class,
            MentionUtils::class => '?'.MentionUtils::class,
            QuestionManager::class => '?'.QuestionManager::class,
            ReportableUtils::class => '?'.ReportableUtils::class,
            SearchUtils::class => '?'.SearchUtils::class,
            TagUtils::class => '?'.TagUtils::class,
            TypableUtils::class => '?'.TypableUtils::class,
            ViewableUtils::class => '?'.ViewableUtils::class,
            VotableUtils::class => '?'.VotableUtils::class,
            WatchableUtils::class => '?'.WatchableUtils::class,
            WitnessManager::class => '?'.WitnessManager::class,
            UserManager::class => '?'.UserManager::class,
        ));
    }

	protected function createLock($name, $blocking = false, $ttl = 300.0, $autoRelease = true) {

		// Lock / Check resource
		if ($blocking) {
			$store = new SemaphoreStore();
		} else {
			$memcached = new \Memcached();
			$memcached->addServer($this->getParameter('memcached_host'), $this->getParameter('memcached_port'));
			$store = new MemcachedStore($memcached);
		}
		$factory = new LockFactory($store);
		$resource = $name.'_'.($this->getUser() ? $this->getUser()->getId() : '');
		$lock = $factory->createLock($resource, $ttl, $autoRelease);
		if (!$lock->acquire($blocking)) {
			throw $this->createNotFoundException('Resource locked ('.$resource.').');
		}

		return $lock;
	}

}
