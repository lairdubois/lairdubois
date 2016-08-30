<?php

namespace Ladb\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Ladb\CoreBundle\Model\VotableInterface;
use Ladb\CoreBundle\Model\VotableParentInterface;

class VotableEvent extends Event {

	private $votable;
	private $votableParent;
	private $data;

	public function __construct(VotableInterface $votable, VotableParentInterface $votableParent, $data = array()) {
		$this->votable = $votable;
		$this->votableParent = $votableParent;
		$this->data = $data;
	}

	// Votable /////

	public function getVotable() {
		return $this->votable;
	}

	// VotableParent /////

	public function getVotableParent() {
		return $this->votableParent;
	}

	// Data /////

	public function getData() {
		return $this->data;
	}

}
