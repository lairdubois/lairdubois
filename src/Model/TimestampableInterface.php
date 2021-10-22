<?php

namespace App\Model;

interface TimestampableInterface extends BasicTimestampableInterface {

	// ChangedAt /////

	public function setChangedAt($changedAt);

	public function getChangedAt();

}
