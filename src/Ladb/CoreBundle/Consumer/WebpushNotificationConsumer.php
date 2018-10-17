<?php
namespace Ladb\CoreBundle\Consumer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Utils\WebpushNotificationUtils;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

class WebpushNotificationConsumer implements ConsumerInterface {

	protected $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	/////

	public function execute(AMQPMessage $msg) {
		$om = $this->container->get('doctrine')->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);

		$msgBody = unserialize($msg->getBody());

		$userId = $msgBody['userId'];
		$body = $msgBody['body'];
		$icon = $msgBody['icon'];
		$link = $msgBody['link'];

		$user = $userRepository->findOneById($userId);
		if (!is_null($user)) {

			// Send notification
			$webpushNotificationUtils = $this->container->get(WebpushNotificationUtils::class);
			$webpushNotificationUtils->sendNotification($user, $body, $icon, $link);

		}

	}

}