<?php

namespace Ladb\CoreBundle\Model;

interface VotableInterface extends IdentifiableInterface, TypableInterface {

	// ParentEntity /////

	public function setParentEntity(VotableParentInterface $parentEntity);

	// ParentEntityType /////

	public function getParentEntityType();

	// ParentEntityId /////

	public function getParentEntityId();

	// ParentEntityField /////

	public function setParentEntityField($parentEntityField);

	public function getParentEntityField();

	// PositiveVoteScore /////

	public function incrementPositiveVoteScore($by = 1);

	public function getPositiveVoteScore();

	// NegativeVoteScore /////

	public function incrementNegativeVoteScore($by = 1);

	public function getNegativeVoteScore();

	// voteScore /////

	public function incrementVoteScore($by = 1);

	public function getVoteScore();

	// voteCount /////

	public function incrementVoteCount($by = 1);

	public function getVoteCount();

}
