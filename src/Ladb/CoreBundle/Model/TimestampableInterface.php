<?php

namespace Ladb\CoreBundle\Model;

interface TimestampableInterface extends BasicTimestampableInterface {

	// ChangedAt /////

	public function setChangedAt($changedAt);

	public function getChangedAt();

}
