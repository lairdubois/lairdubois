<?php

namespace Ladb\CoreBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class EditComment {

	/**
	 * @Assert\NotBlank()
	 * @Assert\Length(min=2, max=2000)
	 */
	private $body;

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

}