<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Message\Message;
use Ladb\CoreBundle\Entity\Message\MessageMeta;
use Ladb\CoreBundle\Entity\Message\Thread;
use Ladb\CoreBundle\Entity\Message\ThreadMeta;
use Ladb\CoreBundle\Entity\User;

class MessageUtils {

	const NAME = 'ladb_core.message_utils';

	protected $om;
	protected $fieldPreprocessorUtils;

	public function __construct(ObjectManager $om, FieldPreprocessorUtils $fieldPreprocessorUtils) {
		$this->om = $om;
		$this->fieldPreprocessorUtils = $fieldPreprocessorUtils;
	}

	public function composeThread(User $sender, $recipients, $subject, $body, $announcement = false, $flush = true) {
		$participants = array( $sender );
		$participants = array_merge($participants, $recipients);

		$thread = new Thread();
		$thread->setCreatedBy($sender);
		$thread->setSubject($subject);
		$thread->setAnnouncement($announcement);
		$thread->setLastMessageDate(new \DateTime());

		$message = new Message();
		$message->setSender($sender);
		$message->setBody($body);
		$this->fieldPreprocessorUtils->preprocessBodyField($message);
		$thread->addMessage($message);

		foreach ($participants as $participant) {

			$threadMeta = new ThreadMeta();
			$threadMeta->setParticipant($participant);
			$threadMeta->setIsDeleted($announcement && $participant === $sender);
			$thread->addMeta($threadMeta);

			if ($announcement && $participant === $sender) {
				continue;
			}

			$messageMeta = new MessageMeta();
			$messageMeta->setParticipant($participant);
			$messageMeta->setIsRead($participant === $sender);
			$message->addMeta($messageMeta);

			if ($participant != $sender) {
				$participant->incrementUnreadMessageCount();
			}

		}

		$this->om->persist($thread);
		if ($flush) {
			$this->om->flush();
		}

		return $thread;
	}

}