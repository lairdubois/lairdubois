<?php
namespace Ladb\CoreBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class FooConsumer implements ConsumerInterface {

	public function execute(AMQPMessage $msg) {
		$foo = unserialize($msg->body);
		echo 'foo '.$foo." successfully downloaded!\n";
	}

}