<?php

namespace Ladb\CoreBundle\Utils;

use BenTools\WebPushBundle\Model\Message\Notification;
use BenTools\WebPushBundle\Registry\WebPushManagerRegistry;
use Ladb\CoreBundle\Entity\Message\Thread;
use Minishlink\WebPush\WebPush;
use Ladb\CoreBundle\Entity\Core\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebpushNotificationUtils extends AbstractContainerAwareUtils {

	public function sendIncomingMessageNotification(User $recipient, User $sender, Thread $thread) {
		$this->sendNotification(
			$recipient,
			'L\'Air du Bois',
			'Nouveau message de '.$sender->getDisplayname(),
			$this->get('router')->generate('core_message_thread_show', array( 'threadId' => $thread->getId()), UrlGeneratorInterface::ABSOLUTE_URL)
		);
	}

	/////

	public function sendNotification(User $user, string $title, string $body, string $link = 'https://www.lairdubois.fr') {

		$webpush = $this->container->get(WebPush::class);
		$managers = $this->container->get(WebPushManagerRegistry::class);
		$myUserManager = $managers->getManager($user);

		foreach ($myUserManager->findByUser($user) as $subscription) {
			$webpush->sendNotification(
				$subscription->getEndpoint(),
				$this->createNotification($title, $body, $link),
				$subscription->getPublicKey(),
				$subscription->getAuthToken()
			);
		}
		$results = $webpush->flush();

	}

	private function createNotification(string $title, string $body, string $link): Notification {
		return new Notification([
			'title' => $title,
			'body'  => $body,
			'icon'  => 'https://www.lairdubois.fr/favicon-144x144.png',
			'data'  => [
				'link' => $link,
			],
		]);
	}

}