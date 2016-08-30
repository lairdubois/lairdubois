<?php

namespace Ladb\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Ladb\CoreBundle\Entity\Knowledge\AbstractKnowledge;

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
