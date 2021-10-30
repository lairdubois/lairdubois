<?php

namespace App\Manager\Core;

use App\Entity\Core\Registration;
use App\Entity\Core\User;
use App\Manager\AbstractManager;
use App\Utils\GlobalUtils;
use App\Utils\MailerUtils;
use App\Utils\MessageUtils;
use App\Utils\StringUtils;
use App\Utils\UserUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Twig\Environment;

class UserManager extends AbstractManager {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            'templating' => '?'.Environment::class,
            '?'.UserPasswordHasherInterface::class,
            '?'.GlobalUtils::class,
            '?'.MessageUtils::class,
            '?'.MailerUtils::class,
            '?'.StringUtils::class,
            '?'.UserUtils::class,
        ));
    }

    /////

    public function create(string $username, string $email, string $plainPassword, array $roles = null) {
        $user = new User();

        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPlainPassword($plainPassword);

        return $this->createFromEntity($user, $roles);
    }

    public function createFromEntity(User $user, array $roles = null) {
        $om = $this->getDoctrine()->getManager();
        $userRepository = $om->getRepository(User::CLASS_NAME);
        $stringUtils = $this->get(StringUtils::class);
        $userUtils = $this->get(UserUtils::class);
        $passwordHasher = $this->get(UserPasswordHasherInterface::class);

        // Validation /////

        if (!is_null($user->getId())) {
            throw new \Exception('Already persisted user.');
        }
        if (is_null($user->getUsername())) {
            throw new \Exception('No username.');
        }
        if (is_null($user->getEmail())) {
            throw new \Exception('No email.');
        }

        // TODO : Add better validation process

        // Fill fields /////

        $user->setPassword($passwordHasher->hashPassword($user, $user->getPlainPassword()));
        $user->setCreatedAt(new \DateTime());
        $user->setDisplayname($user->getUsername());
        if (!is_null($roles)) {
            $user->setRoles($roles);
        }

        // TODO : just for debug purpose
        $user->setEmailConfirmed(true);
        // TODO : just for debug purpose

        // Canonicalize fields
        $user->setUsernameCanonical($stringUtils->canonicalize($user->getUsername()));
        $user->setEmailCanonical(strtolower($user->getEmail()));
        $user->setDisplaynameCanonical($user->getUsernameCanonical());

        $user->setEnabled(true);

        $om->persist($user);

        // Post process /////

        $globalUtils = $this->get(GlobalUtils::class);
        $templating = $this->get('templating');
        $messageUtils = $this->get(MessageUtils::class);
        $mailerUtils = $this->get(MailerUtils::class);

        /////

        // Create default avatar
        $userUtils->createDefaultAvatar($user, false);

        /////

        // Log registration
        $registration = new Registration();
        $registration->setUser($user);
        $registration->setClientIp4($globalUtils->getRequest()->getClientIp());
        $registration->setClientUserAgent($globalUtils->getRequest()->server->get('HTTP_USER_AGENT'));

        $om->persist($registration);

        /////

        $om->flush();

        /////

        // Send welcome Message
        $adminUsername = $this->getParameter('admin_username');
        $sender = $userRepository->findOneByUsername($adminUsername);
        if (!is_null($sender)) {
            $messageUtils->composeThread($sender, array( $user ), 'Bienvenue !', $templating->render('Core/User/message-welcome.md.twig', array( 'recipientUser' => $user )), null, true);
        }

        // Send admin email notification
        $mailerUtils->sendNewUserNotificationEmailMessage($user);

        return $user;
    }

}