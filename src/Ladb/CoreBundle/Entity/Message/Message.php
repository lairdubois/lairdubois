<?php

namespace Ladb\CoreBundle\Entity\Message;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\BodiedTrait;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\MultiPicturedTrait;

/**
 * @ORM\Table("tbl_message")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Message\MessageRepository")
 */
class Message implements MultiPicturedInterface, BodiedInterface {

	use MultiPicturedTrait, BodiedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Message\Message';
	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_message_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 */
	protected $pictures;
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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
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

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// Thread /////

	public function getThread() {
		return $this->thread;
	}

	public function setThread($thread) {
		$this->thread = $thread;
		return $this;
	}

	// Sender /////

	public function getSender() {
		return $this->sender;
	}

	public function setSender(\Ladb\CoreBundle\Entity\Core\User $sender) {
		$this->sender = $sender;
		return $this;
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

	public function getIsRead() {
		return $this->isRead;
	}

	public function setIsRead($isRead) {
		$this->isRead = $isRead;
		return $this;
	}

}