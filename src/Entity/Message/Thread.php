<?php

namespace App\Entity\Message;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Model\TypableInterface;

/**
 * @ORM\Table("tbl_message_thread")
 * @ORM\Entity(repositoryClass="App\Repository\Message\ThreadRepository")
 */
class Thread implements TypableInterface {

	const CLASS_NAME = 'App\Entity\Message\Thread';
	const TYPE = 2;

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	private $createdAt;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(name="created_by_user_id", referencedColumnName="id")
	 */
	private $createdBy;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $subject;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $announcement = false;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Message\Message", mappedBy="thread", cascade={"all"})
	 */
	private $messages;

	/**
	 * @ORM\Column(name="message_count", type="integer")
	 */
	private $messageCount = 0;

	/**
	 * @ORM\Column(name="last_message_date", type="datetime", nullable=true)
	 */
	private $lastMessageDate;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Message\ThreadMeta", mappedBy="thread", cascade={"all"})
	 */
	private $metas;

	/////

	private $participants;

	private $unreadMessageCount = 0;

	/////

	public function __construct() {
		$this->messages = new \Doctrine\Common\Collections\ArrayCollection();
		$this->metas = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Type /////

	public function getType() {
		return Thread::TYPE;
	}

	// CreatedAt /////

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// CreatedBy /////

	public function getCreatedBy() {
		return $this->createdBy;
	}

	public function setCreatedBy(\App\Entity\Core\User $createdBy) {
		$this->createdBy = $createdBy;
		return $this;
	}

	// Subject /////

	public function getSubject() {
		return $this->subject;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
		return $this;
	}

	// Annoucement /////

	public function getAnnouncement() {
		return $this->announcement;
	}

	public function setAnnouncement($announcement) {
		$this->announcement = $announcement;
		return $this;
	}

	// Messages /////

	public function addMessage(\App\Entity\Message\Message $message) {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setThread($this);
            $this->messageCount++;
        }
		return $this;
	}

	public function removeMessage(\App\Entity\Message\Message $message) {
		if ($this->messages->removeElement($message)) {
            $message->setThread(null);
            $this->messageCount--;
        }
	}

	public function getMessages() {
		return $this->messages;
	}

	// MessageCount /////

	public function getMessageCount() {
		return $this->messageCount;
	}

	// LastMessageDate /////

	public function getLastMessageDate() {
		return $this->lastMessageDate;
	}

	public function setLastMessageDate(\DateTime $lastMessageDate) {
		$this->lastMessageDate = $lastMessageDate;
		return $this;
	}

	// LastMessageAge /////

	public function getLastMessageAge() {
		return $this->lastMessageDate->diff(new \DateTime());
	}

	// Metas /////

	public function addMeta(\App\Entity\Message\ThreadMeta $meta) {
        if (!$this->metas->contains($meta)) {
            $this->metas[] = $meta;
            $meta->setThread($this);
        }
		return $this;
	}

	public function removeMeta(\App\Entity\Message\ThreadMeta $meta) {
		$this->metas->removeElement($meta);
		$meta->setThread(null);
	}

	public function getMetas() {
		return $this->metas;
	}

	/////

	// Participants /////

	public function getParticipants() {
		if ($this->participants == null) {
			$this->participants = new ArrayCollection();

			foreach ($this->metas as $meta) {
				$this->participants->add($meta->getParticipant());
			}
		}
		return $this->participants;
	}

	// UnreadMessageCount /////

	public function getUnreadMessageCount() {
		return $this->unreadMessageCount;
	}

	public function setUnreadMessageCount($unreadMessageCount) {
		$this->unreadMessageCount = $unreadMessageCount;
		return $this;
	}

}