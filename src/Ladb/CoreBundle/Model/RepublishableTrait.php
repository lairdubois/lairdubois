<?php

namespace Ladb\CoreBundle\Model;

trait RepublishableTrait {

	/////

	// PublishCount /////

	public function incrementPublishCount($by = 1) {
		return $this->publishCount += intval($by);
	}

	public function setPublishCount($publishCount) {
		$this->publishCount = $publishCount;
		return $this;
	}

	public function getPublishCount() {
		return $this->publishCount;
	}

}