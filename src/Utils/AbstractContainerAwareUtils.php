<?php

namespace App\Utils;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

abstract class AbstractContainerAwareUtils implements ServiceSubscriberInterface {

	protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /////

    public static function getSubscribedServices() {
        return array(
            'doctrine' => '?'.ManagerRegistry::class,
            'parameter_bag' => '?'.ParameterBagInterface::class,
            'security.token_storage' => '?'.TokenStorageInterface::class,
            'request_stack' => '?'.RequestStack::class,
            'router' => '?'.RouterInterface::class,
            'templating' => '?'.Environment::class,
            'event_dispatcher' => '?'.EventDispatcherInterface::class,
            '?'.CryptoUtils::class,
            '?'.PaginatorUtils::class,
            '?'.GlobalUtils::class,
        );
    }

	/////

	public function get($id) {
		return $this->container->get($id);
	}

	public function getParameter($name) {
		return $this->get('parameter_bag')->get($name);
	}

	public function getDoctrine() {
		if (!$this->container->has('doctrine')) {
			throw new \LogicException('The DoctrineBundle is not registered in your application.');
		}
		return $this->container->get('doctrine');
	}

}