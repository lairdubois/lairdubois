<?php

namespace Ladb\CoreBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class NewThreadMessage {

	/**
	 * @Assert\Count(min=0, max=4)
	 */
	protected $pictures;
	/**
	 * @Assert\NotBlank(message="Aucun membre n'a ce nom d'utilisateur")
	 * @Ladb\CoreBundle\Validator\Constraints\SelfRecipient()
	 */
	private $recipient;
	/**
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=255)
	 */
	private $subject;
	/**
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=10000)
	 */
	private $body;

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Recipient /////

	public function getRecipient() {
		return $this->recipient;
	}

	public function setRecipient(\Ladb\CoreBundle\Entity\Core\User $recipient) {
		$this->recipient = $recipient;
		return $this;
	}

	// Subject /////

	public function getSubject() {
		return $this->subject;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
	}

	// Body /////

	public function getBody() {
		return $this->body;
	}

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	// Pictures /////

	public function addPicture(\Ladb\CoreBundle\Entity\Core\Picture $picture) {
		if (!$this->pictures->contains($picture)) {
			$this->pictures[] = $picture;
		}
		return $this;
	}

	public function removePicture(\Ladb\CoreBundle\Entity\Core\Picture $picture) {
		$this->pictures->removeElement($picture);
	}

	public function getPictures() {
		return $this->pictures;
	}

}