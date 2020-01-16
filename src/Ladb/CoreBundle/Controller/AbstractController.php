<?php

namespace Ladb\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractController extends Controller {

	protected function createLock($name) {

		// Lock / Check resource
		$store = new \Symfony\Component\Lock\Store\SemaphoreStore();
		$factory = new \Symfony\Component\Lock\Factory($store);
		$lock = $factory->createLock($name.'_'.($this->getUser() ? $this->getUser()->getId() : ''));
		if (!$lock->acquire()) {
			throw $this->createNotFoundException('Resource locked ('.$name.').');
		}

		return $lock;
	}

}