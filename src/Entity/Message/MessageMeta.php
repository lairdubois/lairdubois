<?php

namespace App\Entity\Message;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_message_meta",
 *		uniqueConstraints={
 *			@ORM\UniqueConstraint(name="ENTITY_MESSAGE_META_UNIQUE", columns={"participant_user_id", "message_id"})
 * 		}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\Message\MessageMetaRepository")
 */
class MessageMeta {

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Message\Message", inversedBy="metas")
	 */
	private $message;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(name="participant_user_id", referencedColumnName="id")
	 */
	private $participant;

	/**
	 * @ORM\Column(type="boolean", name="is_read")
	 */
	private $isRead = false;

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Message /////

	public function getMessage() {
		return $this->message;
	}

	public function setMessage(\App\Entity\Message\Message $message) {
		$this->message = $message;
		return $this;
	}

	// Participant /////

	public function getParticipant() {
		return $this->participant;
	}

	public function setParticipant(\App\Entity\Core\User $participant) {
		$this->participant = $participant;
		return $this;
	}

	// IsRead /////

	public function getIsRead() {
		return $this->isRead;
	}

	public function setIsRead($isRead) {
		$this->isRead = $isRead;
		return $this;
	}

}