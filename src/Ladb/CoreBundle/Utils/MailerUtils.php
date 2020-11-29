<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Funding\Donation;
use Ladb\CoreBundle\Entity\Message\Message;
use Ladb\CoreBundle\Entity\Core\Spotlight;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Message\Thread;
use Ladb\CoreBundle\Entity\Core\Report;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailerUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.mailer_utils';

	const LIST_NOTIFICATIONS = 'notifications';
	const LIST_WEEKNEWS = 'weeknews';

	/////

	private function _renderTemplate($name, array $parameters = array()) {
		return $this->get('templating')->render($name, $parameters);
	}

	/////

	public function generateListUnsubscribeLink(User $user, $list) {
		return $this->get('router')->generate('core_user_email_unsubscribe', array(
			'list' => $list,
			'encryptedEmail' => $this->get(CryptoUtils::NAME)->encryptString($user->getEmailCanonical())
		),
		UrlGeneratorInterface::ABSOLUTE_URL);
	}

	/////

	public function sendEmailMessage($toEmail, $subject, $body, $htmlBody = null, $listUnsubscribeLink = null) {
		if (empty($toEmail)) {
			return;	// Invalid email
		}

		// Create the DKIM signer
		$privateKey = file_get_contents(__DIR__.'/../../../../keys/private.pem');
		$domainName = 'lairdubois.fr';
		$selector = 'dkim';		// For dkim._domainkey.lairdubois.fr
		$signer = new \Swift_Signers_DKIMSigner($privateKey, $domainName, $selector);

		// Create the message instance
		$message = \Swift_Message::newInstance()
			->attachSigner($signer)
			->setFrom(array('noreply@lairdubois.fr' => 'L\'Air du Bois'))
			->setTo($toEmail)
			->setSubject($subject)
			->setBody($body)

		;
		if (!is_null($listUnsubscribeLink)) {
			$message->getHeaders()->addTextHeader('List-Unsubscribe', '<mailto:unsubscribe@lairdubois.fr?subject='.$listUnsubscribeLink.'>');
		}
		if (!is_null($htmlBody)) {
			$message->addPart($htmlBody, 'text/html');
		}
		$this->get('mailer')->send($message);
	}

	/////

	public function sendConfirmationEmailMessage(User $recipientUser) {
		if (!$recipientUser->getEmailConfirmed()) {
			$parameters = array(
				'recipientUser' => $recipientUser,
				'token' => $recipientUser->getConfirmationToken(),
			);
			$this->sendEmailMessage(
				$recipientUser->getEmail(),
				'Confirmation de votre adresse e-mail',
				$this->_renderTemplate('LadbCoreBundle:Core/User:email-confirmation.txt.twig', $parameters),
				$this->_renderTemplate('LadbCoreBundle:Core/User:email-confirmation.html.twig', $parameters)
			);
		}
	}

	public function sendIncomingMessageNotificationEmailMessage(User $originRecipientUser, User $recipientUser, User $actorUser, Thread $thread, Message $message) {
		if ($recipientUser->getMeta()->getIncomingMessageEmailNotificationEnabled() && $recipientUser->getEmailConfirmed()) {
			$parameters = array(
				'originRecipientUser' => $originRecipientUser,
				'recipientUser'       => $recipientUser,
				'actorUser'           => $actorUser,
				'thread'              => $thread,
				'message'             => $message,
				'listUnsubscribeLink' => $this->generateListUnsubscribeLink($recipientUser, self::LIST_NOTIFICATIONS),
			);
			$this->sendEmailMessage(
				$recipientUser->getEmail(),
				'Notification de nouveau message de '.$actorUser->getDisplayname(),
				$this->_renderTemplate('LadbCoreBundle:Message:email-notification.txt.twig', $parameters),
				$this->_renderTemplate('LadbCoreBundle:Message:email-notification.html.twig', $parameters),
				$parameters['listUnsubscribeLink']
			);
		}
	}

	public function sendSpamThreadNotificationEmailMessage(User $sender, $recipients, $subject, $body) {
		$this->sendEmailMessage(
			'contact@lairdubois.fr',
			'Notification de détection de SPAM',
			$this->_renderTemplate('LadbCoreBundle:Message:spam-email-notification.txt.twig', array( 'sender' => $sender, 'recipients' => $recipients, 'subject' => $subject, 'body' => $body ))
		);
	}

	public function sendNewSpotlightNotificationEmailMessage(User $recipientUser, Spotlight $spotlight, $entity, $twitterSuccess, $facebookSuccess, $mastodonSuccess) {
		if ($recipientUser->getMeta()->getNewSpotlightEmailNotificationEnabled() && $recipientUser->getEmailConfirmed()) {
			$parameters = array(
				'recipientUser'       => $recipientUser,
				'entity'              => $entity,
				'twitterSuccess'      => $twitterSuccess,
				'facebookSuccess'     => $facebookSuccess,
				'mastodonSuccess'     => $mastodonSuccess,
				'listUnsubscribeLink' => $this->generateListUnsubscribeLink($recipientUser, self::LIST_NOTIFICATIONS),
			);
			$this->sendEmailMessage(
				$recipientUser->getEmail(),
				'Notification de nouveau coup de projecteur',
				$this->_renderTemplate('LadbCoreBundle:Command:spotlight-email-notification.txt.twig', $parameters),
				$this->_renderTemplate('LadbCoreBundle:Command:spotlight-email-notification.html.twig', $parameters),
				$parameters['listUnsubscribeLink']
			);
		}
	}

	public function sendReportNotificationEmailMessage(User $actorUser, Report $report, $entity) {
		$this->sendEmailMessage(
			'contact@lairdubois.fr',
			'Notification de rapport d\'abus',
			$this->_renderTemplate('LadbCoreBundle:Core/Report:email-notification.txt.twig', array( 'actorUser' => $actorUser, 'report' => $report, 'entity' => $entity ))
		);
	}

	public function sendNewUserNotificationEmailMessage(User $actorUser) {
		$this->sendEmailMessage(
			'contact@lairdubois.fr',
			'Notification de nouvel utilisateur',
			$this->_renderTemplate('LadbCoreBundle:Core/User:register-email-notification.txt.twig', array( 'actorUser' => $actorUser ))
		);
	}

	public function sendNewTeamNotificationEmailMessage(User $actorUser, User $team) {
		$this->sendEmailMessage(
			'contact@lairdubois.fr',
			'Notification de nouveau collectif',
			$this->_renderTemplate('LadbCoreBundle:Core/User/Team:new-email-notification.txt.twig', array( 'actorUser' => $actorUser, 'team' => $team ))
		);
	}

	public function sendNewDonationNotificationEmailMessage(User $actorUser, Donation $donation) {
		$this->sendEmailMessage(
			'contact@lairdubois.fr',
			'Notification de nouveau don',
			$this->_renderTemplate('LadbCoreBundle:Funding:donation-email-notification.txt.twig', array( 'actorUser' => $actorUser, 'donation' => $donation ))
		);
	}

	public function sendFundingPaymentReceiptEmailMessage(User $recipientUser, $donation) {
		$parameters = array(
			'recipientUser' => $recipientUser,
			'donation'      => $donation,
		);
		$this->sendEmailMessage(
			$recipientUser->getEmail(),
			'Confirmation du paiement de votre don',
			$this->_renderTemplate('LadbCoreBundle:Funding:payment-receipt-email.txt.twig', $parameters),
			$this->_renderTemplate('LadbCoreBundle:Funding:payment-receipt-email.html.twig', $parameters)
		);
		unset($parameters);
	}

	public function sendOfferExpiredEmailMessage(User $recipientUser, $offer) {
		$parameters = array(
			'recipientUser' => $recipientUser,
			'offer'         => $offer,
		);
		$this->sendEmailMessage(
			$recipientUser->getEmail(),
			'Votre annonce a expiré',
			$this->_renderTemplate('LadbCoreBundle:Offer/Offer:expired-email.txt.twig', $parameters),
			$this->_renderTemplate('LadbCoreBundle:Offer/Offer:expired-email.html.twig', $parameters)
		);
		unset($parameters);
	}

	/////

	public function sendWeekNewsEmailMessage(User &$recipientUser, &$creations, &$questions, &$plans, &$workshops, &$howtos, &$howtoArticles, &$finds, &$posts, &$woods, &$providers, &$schools) {
		if ($recipientUser->getMeta()->getWeekNewsEmailEnabled()) {
			$parameters = array(
				'recipientUser'       => $recipientUser,
				'creations'           => $creations,
				'questions'           => $questions,
				'plans'               => $plans,
				'workshops'           => $workshops,
				'howtos'              => $howtos,
				'howtoArticles'       => $howtoArticles,
				'finds'               => $finds,
				'posts'               => $posts,
				'woods'               => $woods,
				'providers'           => $providers,
				'schools'             => $schools,
				'listUnsubscribeLink' => $this->generateListUnsubscribeLink($recipientUser, self::LIST_WEEKNEWS),
			);
			$this->sendEmailMessage(
				$recipientUser->getEmail(),
				'Nouveautés l\'Air du Bois de la semaine',
				$this->_renderTemplate('LadbCoreBundle:Command:mailing-weeknews-email.txt.twig', $parameters),
				$this->_renderTemplate('LadbCoreBundle:Command:mailing-weeknews-email.html.twig', $parameters),
				$parameters['listUnsubscribeLink']
			);
			unset($parameters);
		}
	}

}