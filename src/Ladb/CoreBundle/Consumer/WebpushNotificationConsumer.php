<?php
namespace Ladb\CoreBundle\Consumer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Utils\WebpushNotificationUtils;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

class WebpushNotificationConsumer implements ConsumerInterface {

	private $logger;
	private $om;
	private $userRepository;
	private $webpushNotificationUtils;

	public function __construct(ContainerInterface $container) {

		$this->logger = $container->get('logger');

		$this->om = $container->get('doctrine')->getManager();
		$this->userRepository = $this->om->getRepository(User::CLASS_NAME);
		$this->webpushNotificationUtils = $container->get(WebpushNotificationUtils::class);

	}

	/////

	public function execute(AMQPMessage $msg) {

		try {

			$msgBody = unserialize($msg->getBody());

			$userId = $msgBody['userId'];
			$body = $msgBody['body'];
			$icon = $msgBody['icon'];
			$link = $msgBody['link'];

		} catch (\Exception $e) {
			$this->logger->error('WebpushNotificationConsumer/execute', array( 'execption' => $e ));
			return false;
		}

		$user = $this->userRepository->findOneById($userId);
		if (!is_null($user)) {

			// Send notification
			$this->webpushNotificationUtils->sendNotification($user, $body, $icon, $link);

		}

		return true;
	}

}