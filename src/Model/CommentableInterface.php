<?php

namespace App\Model;

interface CommentableInterface extends IdentifiableInterface, TypableInterface {

	// CommentCount /////

	public function incrementCommentCount($by = 1);

	public function setCommentCount($commentCount);

	public function getCommentCount();

}
