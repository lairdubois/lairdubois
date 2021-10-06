<?php

namespace App\Model;

interface LikableInterface extends IdentifiableInterface, TypableInterface {

	// LikeCount /////

	public function incrementLikeCount($by = 1);

	public function setLikeCount($likeCount);

	public function getLikeCount();

}
