<?php
namespace Ladb\CoreBundle\Rpc;

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Ratchet\ConnectionInterface;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;

class WorkflowRpc implements RpcInterface {

	protected $pusher;

	/**
	 * @param PusherInterface $pusher
	 */
	public function __construct(PusherInterface $pusher) {
		$this->pusher = $pusher;
	}

	public function doIt(ConnectionInterface $connection, WampRequest $request, $params) {
		print_r($request);
		return array("result" => array_sum($params));
	}

	public function getName() {
		return 'workflow.rpc';
	}

}