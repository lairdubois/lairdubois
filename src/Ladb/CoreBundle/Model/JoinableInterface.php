<?php

namespace Ladb\CoreBundle\Model;

interface JoinableInterface extends IdentifiableInterface, TypableInterface {

	// IsJoinable /////

	public function getIsJoinable();

	// JoinCount /////

	public function incrementJoinCount($by = 1);

	public function setJoinCount($joinCount);

	public function getJoinCount();

}
