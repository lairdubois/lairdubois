<?php

namespace App\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use App\Entity\Workflow\Workflow;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractContainerAwareTopic extends AbstractTopic {

	protected $container;

	/////

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	/////

	public function get($id) {
		return $this->container->get($id);
	}

	public function getParameter($name) {
		return $this->container->getParameter($name);
	}

	public function getDoctrine() {
		if (!$this->container->has('doctrine')) {
			throw new \LogicException('The DoctrineBundle is not registered in your application.');
		}
		return $this->container->get('doctrine');
	}

	public function getClientManipulator() {
		if (!$this->container->has('gos_web_socket.websocket.client_manipulator')) {
			throw new \LogicException('The GosWebSocketBundle is not registered in your application.');
		}
		return $this->container->get('gos_web_socket.websocket.client_manipulator');

	}

}