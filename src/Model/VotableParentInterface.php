<?php

namespace App\Model;

interface VotableParentInterface extends IdentifiableInterface, TypableInterface {

	// PositiveVoteCount /////

	public function incrementPositiveVoteCount($by = 1);

	public function getPositiveVoteCount();

	// NegativeVoteCount /////

	public function incrementNegativeVoteCount($by = 1);

	public function getNegativeVoteCount();

	// VoteCount /////

	public function incrementVoteCount($by = 1);

	public function getVoteCount();

}
