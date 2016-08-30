<?php

namespace Ladb\CoreBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractContainerAwareUtils {

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

}