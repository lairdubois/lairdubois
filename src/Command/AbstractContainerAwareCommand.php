<?php

namespace App\Command;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractContainerAwareCommand extends Command implements ServiceSubscriberInterface {

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container){
        $this->container = $container;
        parent::__construct();
    }

    /////

    public static function getSubscribedServices() {
        return array(
            'doctrine' => '?'.ManagerRegistry::class,
            'parameter_bag' => '?'.ParameterBagInterface::class,
        );
    }

    /////

    public function get($id) {
        return $this->container->get($id);
    }

    public function getParameter($name) {
        return $this->container->get('parameter_bag')->get($name);
    }

    public function getDoctrine() {
        if (!$this->container->has('doctrine')) {
            throw new \LogicException('The DoctrineBundle is not registered in your application.');
        }
        return $this->container->get('doctrine');
    }

}