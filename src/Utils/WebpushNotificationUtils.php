<?php

namespace App\Utils;

use App\Entity\Core\Like;
use App\Entity\Core\MemberInvitation;
use App\Entity\Core\User;
use App\Entity\Message\Thread;
use App\Entity\Qa\Answer;
use App\Entity\Qa\Question;
use App\Messenger\WebpushNotificationMessage;
use App\Model\LikableInterface;
use BenTools\WebPushBundle\Model\Message\PushNotification;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerRegistry;
use BenTools\WebPushBundle\Sender\PushMessageSender;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebpushNotificationUtils extends AbstractContainerAwareUtils {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            'liip_imagine.cache.manager' => '?'.CacheManager::class,
            '?'.TypableUtils::class,
            '?'.MessageBusInterface::class,
            '?'.UserSubscriptionManagerRegistry::class,
            '?'.PushMessageSender::class,
        ));
    }

	public function enqueueNewAnswerNotification(Answer $answer, Question $question) {
		$this->enqueueNotification(
			$question->getUser()->getId(),
			'Nouvelle réponse de '.$answer->getUser()->getDisplayname(),
			$this->get('liip_imagine.cache.manager')->getBrowserPath($answer->getUser()->getAvatar()->getWebPath(), '128x128o'),
			$this->get(TypableUtils::class)->getUrlAction($answer)
		);
	}

	public function enqueueNewLikeNotification(Like $like, LikableInterface $likable) {
		$this->enqueueNotification(
			$like->getEntityUser()->getId(),
			'Nouveau coup de coeur de '.$like->getUser()->getDisplayname(),
			$this->get('liip_imagine.cache.manager')->getBrowserPath($like->getUser()->getAvatar()->getWebPath(), '128x128o'),
			$this->get(TypableUtils::class)->getUrlAction($likable)
		);
	}

	public function enqueueNewMemberInvitationNotification(MemberInvitation $memberInvitation) {
		$this->enqueueNotification(
			$memberInvitation->getRecipient()->getId(),
			$memberInvitation->getSender()->getDisplayname().' vous invite à rejoindre le collectif '.$memberInvitation->getTeam()->getDisplayname(),
			$this->get('liip_imagine.cache.manager')->getBrowserPath($memberInvitation->getTeam()->getAvatar()->getWebPath(), '128x128o'),
			$this->get('router')->generate('core_user_show', array( 'username' => $memberInvitation->getTeam()->getUsernameCanonical()), UrlGeneratorInterface::ABSOLUTE_URL)
		);
	}

	public function enqueueIncomingMessageNotification(User $recipient, User $sender, Thread $thread) {
		$this->enqueueNotification(
			$recipient->getId(),
			'Nouveau message de '.$sender->getDisplayname(),
			$this->get('liip_imagine.cache.manager')->getBrowserPath($sender->getAvatar()->getWebPath(), '128x128o'),
			$this->get('router')->generate('core_message_thread_show', array( 'id' => $thread->getId()), UrlGeneratorInterface::ABSOLUTE_URL)
		);
	}

	public function enqueueNotification($userId, $body, $icon, $link) {
        $messageBus = $this->get(MessageBusInterface::class);
        $messageBus->dispatch(new WebpushNotificationMessage(
            $userId,
            $body,
            $icon,
            $link
        ));
	}

	/////

	public function sendNotification(User $user, string $body, string $icon = 'https://www.lairdubois.fr/favicon-144x144.png', string $link = 'https://www.lairdubois.fr') {

        $userSubscriptionManager = $this->get(UserSubscriptionManagerRegistry::class);
        $sender = $this->get(PushMessageSender::class);

        $subscriptions = $userSubscriptionManager->findByUser($user);
        $notification = new PushNotification('L\'Air du Bois', array(
            PushNotification::BODY => $body,
            PushNotification::ICON => $icon,
            PushNotification::DATA => array(
                'link' => $link,
            ),
        ));

        $responses = $sender->push($notification->createMessage(), $subscriptions);

        foreach ($responses as $response) {
            if ($response->isExpired()) {
                $userSubscriptionManager->delete($response->getSubscription());
            }
        }

//		$webpush = $this->container->get(WebPush::class);
//		$managers = $this->container->get(WebPushManagerRegistry::class);
//		$myUserManager = $managers->getManager($user);
//
//		foreach ($myUserManager->findByUser($user) as $subscription) {
//			$webpush->sendNotification(
//				$subscription->getEndpoint(),
//				new Notification([
//					'title' => 'L\'Air du Bois',
//					'body'  => $body,
//					'icon'  => $icon,
//					'data'  => [
//						'link' => $link,
//					],
//				]),
//				$subscription->getPublicKey(),
//				$subscription->getAuthToken()
//			);
//		}
//		$results = $webpush->flush();
//
//		// Delete expired subscriptions
//		if (is_array($results)) {
//			foreach ($results as $result) {
//				if (!empty($result['expired'])) {
//					foreach ($myUserManager->findByHash($myUserManager->hash($result['endpoint'])) as $subscription) {
//						$myUserManager->delete($subscription);
//					}
//				}
//			}
//		}

	}

}