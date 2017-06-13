<?php

namespace Ladb\CoreBundle\Model;

trait VotableParentTrait {

	// PositiveVoteCount /////

	public function incrementPositiveVoteCount($by = 1) {
		return $this->positiveVoteCount += intval($by);
	}

	public function setPositiveVoteCount($positiveVoteCount) {
		$this->positiveVoteCount = $positiveVoteCount;
		return $this;
	}

	public function getPositiveVoteCount() {
		return $this->positiveVoteCount;
	}

	// NegativeVoteCount /////

	public function incrementNegativeVoteCount($by = 1) {
		return $this->negativeVoteCount += intval($by);
	}

	public function setNegativeVoteCount($negativeVoteCount) {
		$this->negativeVoteCount = $negativeVoteCount;
		return $this;
	}

	public function getNegativeVoteCount() {
		return $this->negativeVoteCount;
	}

	// VoteCount /////

	public function incrementVoteCount($by = 1) {
		return $this->voteCount += intval($by);
	}

	public function setVoteCount($voteCount) {
		$this->voteCount = $voteCount;
		return $this;
	}

	public function getVoteCount() {
		return $this->voteCount;
	}

}