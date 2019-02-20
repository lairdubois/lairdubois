<?php

namespace Ladb\CoreBundle\Model;

trait TimestampableTrait {

	use BasicTimestampableTrait;

	// ChangedAt /////

	public function setChangedAt($changedAt) {
		$this->changedAt = $changedAt;
		return $this;
	}

	public function getChangedAt() {
		return $this->changedAt;
	}

}