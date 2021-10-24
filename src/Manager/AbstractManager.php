<?php

namespace App\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractManager implements ServiceSubscriberInterface {

	protected $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
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
        return $this->get('parameter_bag')->get($name);
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