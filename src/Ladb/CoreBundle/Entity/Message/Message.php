<?php

namespace Ladb\CoreBundle\Entity\Message;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\BodiedTrait;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Model\MultiPicturedTrait;
use Ladb\CoreBundle\Model\TypableInterface;

/**
 * @ORM\Table("tbl_message")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Message\MessageRepository")
 */
class Message implements TypableInterface, MultiPicturedInterface, BodiedInterface {

	use MultiPicturedTrait, BodiedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Message\Message';
	const TYPE = 3;

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
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=10000)
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $htmlBody;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinTable(name="tbl_message_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=0, max=4)
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

	// Type /////

	public function getType() {
		return Message::TYPE;
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

	public function setSender(\Ladb\CoreBundle\Entity\Core\User $sender) {
		$this->sender = $sender;
		return $this;
	}

	public function getSender() {
		return $this->sender;
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