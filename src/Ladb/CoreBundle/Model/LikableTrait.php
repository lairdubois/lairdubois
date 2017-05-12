<?php

namespace Ladb\CoreBundle\Model;

trait LikableTrait {

	// LikeCount /////

	public function incrementLikeCount($by = 1) {
		return $this->likeCount += intval($by);
	}

	public function setLikeCount($likeCount) {
		$this->likeCount = $likeCount;
		return $this;
	}

	public function getLikeCount() {
		return $this->likeCount;
	}

}