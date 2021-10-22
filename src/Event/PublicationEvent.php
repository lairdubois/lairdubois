<?php

namespace App\Event;

use App\Model\PublicationInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PublicationEvent extends Event {

	private $publication;
	private $data;

	public function __construct(PublicationInterface $publication, $data = array()) {
		$this->publication = $publication;
		$this->data = $data;
	}

	// Publication /////

	public function getPublication() {
		return $this->publication;
	}

	// Data /////

	public function getData() {
		return $this->data;
	}

}
