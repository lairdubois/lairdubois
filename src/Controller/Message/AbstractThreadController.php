<?php

namespace App\Controller\Message;

use App\Controller\AbstractController;
use App\Entity\Core\Member;
use App\Entity\Core\User;
use App\Entity\Message\Message;
use App\Entity\Message\Thread;
use App\Utils\MailerUtils;
use App\Utils\WebpushNotificationUtils;

abstract class AbstractThreadController extends AbstractController {

	protected function retrieveThread($id) {
		$om = $this->getDoctrine()->getManager();
		$threadRepository = $om->getRepository(Thread::CLASS_NAME);
		$thread = $threadRepository->findOneByIdJoinedOnMetaAndParticipant($id);
		if (is_null($thread)) {
			throw $this->createNotFoundException('Unable to find Thread entity (id='.$id.').');
		}
		return $thread;
	}

	/////

	protected function assertShowable(Thread $thread) {
		if (!$thread->getParticipants()->contains($this->getUser())) {

			$om = $this->getDoctrine()->getManager();
			$memberRepository = $om->getRepository(Member::CLASS_NAME);
			$allowed = false;
			foreach ($thread->getParticipants() as $participant) {
				if ($memberRepository->existsByTeamAndUser($participant, $this->getUser())) {
					$allowed = true;
					break;
				}
			}

			if (!$allowed) {
				throw $this->createNotFoundException('Not allowed');
			}
		}
	}

	protected function assertDeletable(Thread $thread) {
		if (!$thread->getParticipants()->contains($this->getUser())) {
			throw $this->createNotFoundException('Not allowed');
		}
	}

	/////

	protected function notifyRecipientsForIncomingMessage(array $recipients, User $sender, Thread $thread, Message $message) {

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);
		$mailerUtils = $this->get(MailerUtils::class);
		$webpushNotificationUtils = $this->get(WebpushNotificationUtils::class);
		$notifiedRecipients = array();
		foreach ($recipients as $recipient) {

			if ($recipient->getIsTeam()) {
				$members = $memberRepository->findPaginedByTeam($recipient);
				foreach ($members as $member) {

					if (in_array($member->getUser(), $notifiedRecipients) || $member->getUser() == $sender) {
						continue;
					}

					// Email notification
					$mailerUtils->sendIncomingMessageNotificationEmailMessage($recipient, $member->getUser(), $sender, $thread, $message);

					// Webpush notification
					$webpushNotificationUtils->enqueueIncomingMessageNotification($member->getUser(), $sender, $thread);

					// Increment unread message count
					$member->getUser()->getMeta()->incrementUnreadMessageCount();

					$notifiedRecipients[] = $member->getUser();
				}
			} else {

				if (in_array($recipient, $notifiedRecipients)) {
					continue;
				}

				// Email notification
				$mailerUtils->sendIncomingMessageNotificationEmailMessage($recipient, $recipient, $sender, $thread, $thread->getMessages()->last());

				// Webpush notification
				$webpushNotificationUtils->enqueueIncomingMessageNotification($recipient, $sender, $thread);

				// Increment unread message count
				$recipient->getMeta()->incrementUnreadMessageCount();

				$notifiedRecipients[] = $recipient;
			}

		}

	}

}
