<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class NewThreadMessage {

	/**
	 * Assert\NotBlank(message="Aucun membre n'a ce nom d'utilisateur")
	 */

	/**
	 * @App\Validator\Constraints\SelfRecipient()
	 * @Assert\Count(min=1, max=20)
	 */
	private $recipients;

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

	/**
	 * @Assert\Count(min=0, max=4)
	 */
	protected $pictures;

	/////

	public function __construct() {
		$this->recipients = new \Doctrine\Common\Collections\ArrayCollection();
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// Recipients /////

	public function addRecipient(\App\Entity\Core\User $recipient) {
		if (!$this->recipients->contains($recipient)) {
			$this->recipients[] = $recipient;
		}
		return $this;
	}

	public function removeRecipient(\App\Entity\Core\User $recipient) {
		$this->recipients->removeElement($recipient);
	}

	public function getRecipients() {
		return $this->recipients;
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

	public function addPicture(\App\Entity\Core\Picture $picture) {
		if (!$this->pictures->contains($picture)) {
			$this->pictures[] = $picture;
		}
		return $this;
	}

	public function removePicture(\App\Entity\Core\Picture $picture) {
		$this->pictures->removeElement($picture);
	}

	public function getPictures() {
		return $this->pictures;
	}

}