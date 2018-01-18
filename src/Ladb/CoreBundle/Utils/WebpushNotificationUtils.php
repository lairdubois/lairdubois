<?php

namespace Ladb\CoreBundle\Utils;

use BenTools\WebPushBundle\Model\Message\Notification;
use BenTools\WebPushBundle\Registry\WebPushManagerRegistry;
use Ladb\CoreBundle\Entity\Core\Like;
use Ladb\CoreBundle\Entity\Message\Thread;
use Ladb\CoreBundle\Model\TypableInterface;
use Minishlink\WebPush\WebPush;
use Ladb\CoreBundle\Entity\Core\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebpushNotificationUtils extends AbstractContainerAwareUtils {

	public function sendIncomingMessageNotification(User $recipient, User $sender, Thread $thread) {
		$this->sendNotification(
			$recipient,
			'Nouveau message de '.$sender->getDisplayname(),
			$this->get('router')->generate('core_message_thread_show', array( 'threadId' => $thread->getId()), UrlGeneratorInterface::ABSOLUTE_URL)
		);
	}

	public function sendNewLikeNotification(Like $like, TypableInterface $typable) {
		$this->sendNotification(
			$like->getEntityUser(),
			'Nouveau coup de coeur de '.$like->getUser()->getDisplayname(),
			$this->get(TypableUtils::NAME)->getUrlAction($typable)
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

		// Delete expired subscriptions
		if (is_array($results)) {
			foreach ($results as $result) {
				if (!empty($result['expired'])) {
					foreach ($myUserManager->findByHash($myUserManager->hash($result['endpoint'])) as $subscription) {
						$myUserManager->delete($subscription);
					}
				}
			}
		}

	}

	private function createNotification(string $body, string $link): Notification {
		return new Notification([
			'title' => 'L\'Air du Bois',
			'body'  => $body,
			'icon'  => 'https://www.lairdubois.fr/favicon-144x144.png',
			'data'  => [
				'link' => $link,
			],
		]);
	}

}