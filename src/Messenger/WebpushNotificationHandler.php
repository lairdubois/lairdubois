<?php

namespace App\Messenger;

use App\Entity\Core\User;
use App\Utils\WebpushNotificationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class WebpushNotificationHandler implements MessageHandlerInterface {

    private WebpushNotificationUtils $webpushNotificationUtils;

    public function __construct(EntityManagerInterface $om, WebpushNotificationUtils $webpushNotificationUtils, LoggerInterface $logger) {
        $this->om = $om;
        $this->webpushNotificationUtils = $webpushNotificationUtils;
        $this->logger = $logger;
    }

    public function __invoke(WebpushNotificationMessage $message) {
        $userRepository = $this->om->getRepository(User::class);
        $user = $userRepository->findOneById($message->getUserId());
        if (!is_null($user)) {

            // Send notification
            $this->webpushNotificationUtils->sendNotification($user, $message->getBody(), $message->getIcon(), $message->getLink());

        }
    }

}