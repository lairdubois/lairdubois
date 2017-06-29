<?php

namespace Ladb\CoreBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractManager {

	protected $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
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