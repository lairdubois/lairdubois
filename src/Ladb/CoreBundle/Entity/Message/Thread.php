<?php

namespace Ladb\CoreBundle\Entity\Message;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_message_thread")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Message\ThreadRepository")
 */
class Thread {

	const CLASS_NAME = 'LadbCoreBundle:Message\Thread';

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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
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
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Message\Message", mappedBy="thread", cascade={"all"})
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
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Message\ThreadMeta", mappedBy="thread", cascade={"all"})
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

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// CreatedBy /////

	public function setCreatedBy(\Ladb\CoreBundle\Entity\User $createdBy) {
		$this->createdBy = $createdBy;
		return $this;
	}

	public function getCreatedBy() {
		return $this->createdBy;
	}

	// Subject /////

	public function setSubject($subject) {
		$this->subject = $subject;
		return $this;
	}

	public function getSubject() {
		return $this->subject;
	}

	// Annoucement /////

	public function setAnnouncement($announcement) {
		$this->announcement = $announcement;
		return $this;
	}

	public function getAnnouncement() {
		return $this->announcement;
	}

	// Messages /////

	public function addMessage(\Ladb\CoreBundle\Entity\Message\Message $message) {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setThread($this);
            $this->messageCount++;
        }
		return $this;
	}

	public function removeMessage(\Ladb\CoreBundle\Entity\Message\Message $message) {
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

	public function setLastMessageDate(\DateTime $lastMessageDate) {
		$this->lastMessageDate = $lastMessageDate;
		return $this;
	}

	public function getLastMessageDate() {
		return $this->lastMessageDate;
	}

	// LastMessageAge /////

	public function getLastMessageAge() {
		return $this->lastMessageDate->diff(new \DateTime());
	}

	// Metas /////

	public function addMeta(\Ladb\CoreBundle\Entity\Message\ThreadMeta $meta) {
        if (!$this->metas->contains($meta)) {
            $this->metas[] = $meta;
            $meta->setThread($this);
        }
		return $this;
	}

	public function removeMeta(\Ladb\CoreBundle\Entity\Message\ThreadMeta $meta) {
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

	public function setUnreadMessageCount($unreadMessageCount) {
		$this->unreadMessageCount = $unreadMessageCount;
		return $this;
	}

	public function getUnreadMessageCount() {
		return $this->unreadMessageCount;
	}

}