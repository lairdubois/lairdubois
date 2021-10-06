<?php

namespace App\Event;

use App\Fos\UserManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use App\Entity\Core\Registration;
use App\Utils\MessageUtils;
use App\Utils\MailerUtils;
use App\Utils\UserUtils;

class UserListener implements EventSubscriberInterface {

	private $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	/////

	public static function getSubscribedEvents() {
		return array(
			// FOSUserEvents::REGISTRATION_COMPLETED => 'onRegistrationCompleted',
		);
	}

	/////

	public function onRegistrationCompleted(FilterUserResponseEvent $event) {
		$userManager = $this->container->get(UserManager::class);
		$templating = $this->container->get('templating');
		$messageUtils = $this->container->get(MessageUtils::class);
		$mailerUtils = $this->container->get(MailerUtils::class);
		$userUtils = $this->container->get(UserUtils::class);
		$om = $this->container->get('doctrine.orm.entity_manager');

		$user = $event->getUser();

		/////

		// Create default avatar
		$userUtils->createDefaultAvatar($user, false);

		/////

		$registration = new Registration();
		$registration->setUser($user);
		$registration->setClientIp4($event->getRequest()->getClientIp());
		$registration->setClientUserAgent($event->getRequest()->server->get('HTTP_USER_AGENT'));

		$om->persist($registration);
		$om->flush();

		/////

		// Send welcome Message
		$adminUsername = $this->container->getParameter('admin_username');
		$sender = $userManager->findUserByUsername($adminUsername);
		if (!is_null($sender)) {
			$messageUtils->composeThread($sender, array( $user ), 'Bienvenue !', $templating->render('Core/User:message-welcome.md.twig', array( 'recipientUser' => $user )), null, true);
		}

		// Send admin email notification
		$mailerUtils->sendNewUserNotificationEmailMessage($user);

	}

}