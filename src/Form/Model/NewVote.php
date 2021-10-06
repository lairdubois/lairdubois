<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;

class NewVote {

	/**
	 * @Assert\NotBlank(groups={"down"})
	 * @Assert\Length(min=10, max=10000, groups={"down"})
	 * @LadbAssert\NoMediaLink()
	 */
	private $body;

	// Body /////

	public function getBody() {
		return $this->body;
	}

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

}