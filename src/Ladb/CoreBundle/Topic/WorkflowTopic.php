<?php

namespace Ladb\CoreBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Symfony\Component\Security\Core\User\UserInterface;

class WorkflowTopic extends AbstractContainerAwareTopic {

	/**
	 * This will receive any Subscription requests for this topic.
	 *
	 * @param ConnectionInterface $connection
	 * @param Topic $topic
	 * @param WampRequest $request
	 * @return void
	 */
	public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) {
		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);

		$id = intval($request->getAttributes()->get('id'));
		$user = $this->getClientManipulator()->getClient($connection);

		$workflow = $workflowRepository->findOneById($id);
		if (is_null($workflow)) {
			$connection->close();
		}
		if (!$user instanceof UserInterface || !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser()->getId() != $user->getId()) {
			$connection->close();
		}

		//this will broadcast the message to ALL subscribers of this topic.
		$topic->broadcast(['data' => $connection->resourceId." has joined ".$topic->getId()]);
	}

	/**
	 * This will receive any UnSubscription requests for this topic.
	 *
	 * @param ConnectionInterface $connection
	 * @param Topic $topic
	 * @param WampRequest $request
	 * @return void
	 */
	public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) {
		//this will broadcast the message to ALL subscribers of this topic.
		$topic->broadcast(['data' => $connection->resourceId." has left ".$topic->getId()]);
	}


	/**
	 * This will receive any Publish requests for this topic.
	 *
	 * @param ConnectionInterface $connection
	 * @param Topic $topic
	 * @param WampRequest $request
	 * @param $event
	 * @param array $exclude
	 * @param array $eligible
	 * @return mixed|void
	 */
	public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible) {
		$topic->broadcast($event);
	}

	/**
	 * @param Topic $topic
	 * @param WampRequest $request
	 * @param array|string $data
	 * @param string $provider The name of pusher who push the data
	 */
	public function onPush(Topic $topic, WampRequest $request, $data, $provider) {
		$topic->broadcast($data);
	}

	/**
	 * Like RPC is will use to prefix the channel
	 * @return string
	 */
	public function getName() {
		return 'workflow.topic';
	}

}