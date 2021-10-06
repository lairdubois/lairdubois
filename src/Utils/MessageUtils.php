<?php

namespace App\Utils;

use Doctrine\Persistence\ObjectManager;
use App\Entity\Message\Message;
use App\Entity\Message\MessageMeta;
use App\Entity\Message\Thread;
use App\Entity\Message\ThreadMeta;
use App\Entity\Core\User;

class MessageUtils {

	const NAME = 'ladb_core.message_utils';

	protected $om;
	protected $fieldPreprocessorUtils;

	public function __construct(ObjectManager $om, FieldPreprocessorUtils $fieldPreprocessorUtils) {
		$this->om = $om;
		$this->fieldPreprocessorUtils = $fieldPreprocessorUtils;
	}

	public function composeThread(User $sender, $recipients, $subject, $body, $pictures = null, $announcement = false, $flush = true) {
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
		if (!is_null($pictures)) {
			foreach ($pictures as $picture) {
				$message->addPicture($picture);
			}
		}
		$this->fieldPreprocessorUtils->preprocessFields($message);
		$thread->addMessage($message);

		foreach ($participants as $participant) {

			$threadMeta = new ThreadMeta();
			$threadMeta->setParticipant($participant);
			$threadMeta->setIsDeleted($announcement && $participant === $sender);
			$thread->addMeta($threadMeta);

			if ($announcement && $participant === $sender || $participant->getIsTeam()) {
				continue;
			}

			$messageMeta = new MessageMeta();
			$messageMeta->setParticipant($participant);
			$messageMeta->setIsRead($participant === $sender);
			$message->addMeta($messageMeta);

			if ($participant != $sender) {
				$participant->getMeta()->incrementUnreadMessageCount();
			}

		}

		$this->om->persist($thread);
		if ($flush) {
			$this->om->flush();
		}

		return $thread;
	}

}