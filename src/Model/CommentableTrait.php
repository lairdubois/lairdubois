<?php

namespace App\Model;

trait CommentableTrait {

	// CommentCount /////

	public function incrementCommentCount($by = 1) {
		return $this->commentCount += intval($by);
	}

	public function setCommentCount($commentCount) {
		$this->commentCount = $commentCount;
		return $this;
	}

	public function getCommentCount() {
		return $this->commentCount;
	}

}