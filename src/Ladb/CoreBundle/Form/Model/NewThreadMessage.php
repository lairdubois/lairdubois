<?php

namespace Ladb\CoreBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class NewThreadMessage {

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

	// Recipient /////

	public function setRecipient(\Ladb\CoreBundle\Entity\User $recipient) {
		$this->recipient = $recipient;
		return $this;
	}

	public function getRecipient() {
		return $this->recipient;
	}

	// Subject /////

	public function setSubject($subject) {
		$this->subject = $subject;
	}

	public function getSubject() {
		return $this->subject;
	}

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

}