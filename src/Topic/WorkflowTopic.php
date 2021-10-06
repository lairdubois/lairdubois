<?php

namespace App\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use App\Entity\User;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use App\Entity\Workflow\Workflow;
use Symfony\Component\Security\Core\User\UserInterface;

class WorkflowTopic extends AbstractContainerAwareTopic {

	private function _retrieveWorkflow(ConnectionInterface $connection, WampRequest $request, $user) {
		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);

		$id = intval($request->getAttributes()->get('id'));

		$workflow = $workflowRepository->findOneById($id);
		if (is_null($workflow)) {
			$this->get('logger')->error('Unable to find Workflow entity (id='.$id.').');
			$connection->close();
			return null;
		}
		if (!$user instanceof UserInterface || !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $workflow->getUser() != $user) {
			$this->get('logger')->error('Not allowed');
			$connection->close();
			return null;
		}

		return $workflow;
	}

	/////

	/**
	 * This will receive any Subscription requests for this topic.
	 *
	 * @param ConnectionInterface $connection
	 * @param Topic $topic
	 * @param WampRequest $request
	 * @return void
	 */
	public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) {

		// Retrieve user
		$user = $this->getUserByConnection($connection);

		// Retieve and check workflow
		$workflow = $this->_retrieveWorkflow($connection, $request, $user);

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
		//$topic->broadcast(['data' => $connection->resourceId." has left ".$topic->getId()]);
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
	 * Like RPC is will use to prefix the channel
	 * @return string
	 */
	public function getName(): string {
		return 'workflow.topic';
	}

}