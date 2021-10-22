<?php

namespace App\Entity\Message;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_message_thread_meta")
 * @ORM\Entity(repositoryClass="App\Repository\Message\ThreadMetaRepository")
 */
class ThreadMeta {

	const CLASS_NAME = 'App\Entity\Message\ThreadMeta';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Message\Thread", inversedBy="metas")
	 */
	private $thread;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(name="participant_user_id", referencedColumnName="id")
	 */
	private $participant;

	/**
	 * @ORM\Column(type="boolean", name="is_deleted")
	 */
	private $isDeleted = false;

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Message /////

	public function getThread() {
		return $this->thread;
	}

	public function setThread(\App\Entity\Message\Thread $thread) {
		$this->thread = $thread;
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

	// IsDeleted /////

	public function getIsDeleted() {
		return $this->isDeleted;
	}

	public function setIsDeleted($isDeleted) {
		$this->isDeleted = $isDeleted;
		return $this;
	}
}