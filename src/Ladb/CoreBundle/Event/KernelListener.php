<?php

namespace Ladb\CoreBundle\Event;

use Ladb\CoreBundle\Utils\GlobalUtils;
use Ladb\CoreBundle\Utils\UserUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelListener implements EventSubscriberInterface {

	private $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public static function getSubscribedEvents() {
		return array(
			KernelEvents::CONTROLLER => 'onKernelController',
		);
	}

	/////

	public function onKernelController(FilterControllerEvent $event) {
		if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
			return;	// Exclude subrequests
		}
		if ($event->getRequest()->isXmlHttpRequest()) {
			return;	// Exclude AJAX requests
		}
		$_controller = $event->getRequest()->attributes->get('_controller');
		if (strrpos($_controller, '::welcome') === false && strrpos($_controller, '::show') === false && strrpos($_controller, '::list') === false) {
			return;	// Exclude all non LADB  show or list routes
		}

		// Retrieve current user
		$globalUtils = $this->container->get(GlobalUtils::NAME);
		$user = $globalUtils->getUser();
		if (is_null($user)) {
			return;
		}

//		// Compute unlisted counters
//		$userUtils = $this->container->get(UserUtils::NAME);
//		$userUtils->computeUnlistedCounters($user);

	}

}