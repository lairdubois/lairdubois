<?php

namespace App\Model;

interface WatchableInterface extends IdentifiableInterface, TypableInterface, TitledInterface {

	// WatchCount /////

	public function incrementWatchCount($by = 1);

	public function setWatchCount($likeCount);

	public function getWatchCount();

}
