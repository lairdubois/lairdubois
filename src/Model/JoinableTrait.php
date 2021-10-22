<?php

namespace App\Model;

trait JoinableTrait {

	// IsJoinable /////

	public function getIsJoinable() {
		return $this->getIsPublic();
	}

	// JoinCount /////

	public function incrementJoinCount($by = 1) {
		return $this->joinCount += intval($by);
	}

	public function getJoinCount() {
		return $this->joinCount;
	}

	public function setJoinCount($joinCount) {
		$this->joinCount = $joinCount;
	}

}
