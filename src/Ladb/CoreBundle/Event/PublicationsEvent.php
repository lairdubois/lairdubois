<?php

namespace Ladb\CoreBundle\Event;

use Ladb\CoreBundle\Model\PublicationInterface;
use Symfony\Component\EventDispatcher\Event;

class PublicationsEvent extends Event {

	private $publications;

	public function __construct($publications) {
		$this->publications = $publications;
	}

	// Publications /////

	public function getPublications() {
		return $this->publications;
	}

}
