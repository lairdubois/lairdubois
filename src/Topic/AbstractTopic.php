<?php

namespace App\Topic;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use App\Entity\Workflow\Workflow;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractTopic implements TopicInterface {

	protected function getUserByConnection(ConnectionInterface $connection) {
		return $this->getClientManipulator()->getClient($connection);
	}

}