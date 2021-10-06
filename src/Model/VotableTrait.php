<?php

namespace App\Model;

trait VotableTrait {

	// ParentEntity /////

	public function setParentEntity(VotableParentInterface $parentEntity) {
		$this->parentEntityType = $parentEntity->getType();
		$this->parentEntityId = $parentEntity->getId();
		return $this;
	}

	// ParentEntityType /////

	public function setParentEntityType($parentEntityType) {
		$this->parentEntityType = $parentEntityType;
		return $this;
	}

	public function getParentEntityType() {
		return $this->parentEntityType;
	}

	// ParentEntityId /////

	public function setParentEntityId($parentEntityId) {
		$this->parentEntityId = $parentEntityId;
		return $this;
	}

	public function getParentEntityId() {
		return $this->parentEntityId;
	}

	// ParentEntityField /////

	public function setParentEntityField($parentEntityField) {
		$this->parentEntityField = $parentEntityField;
		return $this;
	}

	public function getParentEntityField() {
		return $this->parentEntityField;
	}

	// PositiveVoteScore /////

	public function incrementPositiveVoteScore($by = 1) {
		return $this->positiveVoteScore += intval($by);
	}

	public function setPositiveVoteScore($positiveVoteScore) {
		return $this->positiveVoteScore = $positiveVoteScore;
	}

	public function getPositiveVoteScore() {
		return $this->positiveVoteScore;
	}

	// NegativeVoteScore /////

	public function incrementNegativeVoteScore($by = 1) {
		return $this->negativeVoteScore += intval($by);
	}

	public function setNegativeVoteScore($negativeVoteScore) {
		return $this->negativeVoteScore = $negativeVoteScore;
	}

	public function getNegativeVoteScore() {
		return $this->negativeVoteScore;
	}

	// voteScore /////

	public function incrementVoteScore($by = 1) {
		return $this->voteScore += intval($by);
	}

	public function setVoteScore($voteScore) {
		return $this->voteScore = $voteScore;
	}

	public function getVoteScore() {
		return $this->voteScore;
	}

	// voteCount /////

	public function incrementVoteCount($by = 1) {
		return $this->voteCount += intval($by);
	}

	public function setVoteCount($voteCount) {
		return $this->voteCount = $voteCount;
	}

	public function getVoteCount() {
		return $this->voteCount;
	}

}