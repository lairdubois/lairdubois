<?php

namespace Ladb\CoreBundle\Model;

interface WatchableChildInterface extends TypableInterface  {

	// ParentEntityType /////

	public function getParentEntityType();

	// ParentEntityId /////

	public function getParentEntityId();

}
