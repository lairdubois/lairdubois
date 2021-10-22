<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Entity\Knowledge\AbstractKnowledge;

class KnowledgeEvent extends Event {

	private $knowledge;
	private $data;

	public function __construct(AbstractKnowledge $knowledge, $data = array()) {
		$this->knowledge = $knowledge;
		$this->data = $data;
	}

	// Knowledge /////

	public function getKnowledge() {
		return $this->knowledge;
	}

	// Data /////

	public function getData() {
		return $this->data;
	}

}
