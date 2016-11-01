<?php

namespace Ladb\CoreBundle\Entity\Message;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\BodiedInterface;

/**
 * @ORM\Table("tbl_message")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Message\MessageRepository")
 */
class Message implements BodiedInterface {

	const CLASS_NAME = 'LadbCoreBundle:Message\Message';

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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Message\Thread", inversedBy="messages")
	 * @ORM\JoinColumn(name="thread_id", referencedColumnName="id")
	 */
	private $thread;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
	 * @ORM\JoinColumn(name="sender_user_id", referencedColumnName="id")
	 */
	private $sender;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $htmlBody;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_message_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 */
	protected $pictures;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Message\MessageMeta", mappedBy="message", cascade={"all"})
	 */
	private $metas;

	/////

	private $isRead = true;

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
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

	// Thread /////

	public function setThread($thread) {
		$this->thread = $thread;
		return $this;
	}

	public function getThread() {
		return $this->thread;
	}

	// Sender /////

	public function setSender(\Ladb\CoreBundle\Entity\User $sender) {
		$this->sender = $sender;
		return $this;
	}

	public function getSender() {
		return $this->sender;
	}

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

	// HtmlBody /////

	public function setHtmlBody($htmlBody) {
		$this->htmlBody = $htmlBody;
		return $this;
	}

	public function getHtmlBody() {
		return $this->htmlBody;
	}

	// Pictures /////

	public function addPicture(\Ladb\CoreBundle\Entity\Picture $picture) {
		if (!$this->pictures->contains($picture)) {
			$this->pictures[] = $picture;
		}
		return $this;
	}

	public function removePicture(\Ladb\CoreBundle\Entity\Picture $picture) {
		$this->pictures->removeElement($picture);
	}

	public function getPictures() {
		return $this->pictures;
	}

	public function resetPictures() {
		$this->pictures->clear();
	}

	// Metas /////

	public function addMeta(\Ladb\CoreBundle\Entity\Message\MessageMeta $meta) {
		$this->metas[] = $meta;
		$meta->setMessage($this);
		return $this;
	}

	public function removeMeta(\Ladb\CoreBundle\Entity\Message\MessageMeta $meta) {
		$this->metas->removeElement($meta);
		$meta->setMessage(null);
	}

	public function getMetas() {
		return $this->metas;
	}

	/////

	// IsRead /////

	public function setIsRead($isRead) {
		$this->isRead = $isRead;
		return $this;
	}

	public function getIsRead() {
		return $this->isRead;
	}

}