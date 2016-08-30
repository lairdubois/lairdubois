<?php

namespace Ladb\CoreBundle\Event;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use Ladb\CoreBundle\Entity\Registration;
use Ladb\CoreBundle\Utils\MessageUtils;
use Ladb\CoreBundle\Utils\MailerUtils;
use Ladb\CoreBundle\Utils\UserUtils;

class UserListener implements EventSubscriberInterface {

	private $container;

	public function __construct(ContainerInterface $userManager) {
		$this->container = $userManager;
	}

	public static function getSubscribedEvents() {
		return array(
			FOSUserEvents::REGISTRATION_COMPLETED => 'onRegistrationCompleted',
		);
	}

	/////

	public function onRegistrationCompleted(FilterUserResponseEvent $event) {
		$userManager = $this->container->get('fos_user.user_manager');
		$templating = $this->container->get('templating');
		$messageUtils = $this->container->get(MessageUtils::NAME);
		$mailerUtils = $this->container->get(MailerUtils::NAME);
		$userUtils = $this->container->get(UserUtils::NAME);
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
		$sender = $userManager->findUserByUsername('zeloko');
		$messageUtils->composeThread($sender, array( $user ), 'Bienvenue !', $templating->render('LadbCoreBundle:User:message-welcome.md.twig', array( 'recipientUser' => $user )), true);

		// Send admin email notification
		$mailerUtils->sendNewUserNotificationEmailMessage($user);

	}

}