<?php

namespace App\Model;

interface TaggableInterface extends TypableInterface {

	// Tags /////

	public function addTag(\App\Entity\Core\Tag $tag);

	public function removeTag(\App\Entity\Core\Tag $tag);

	public function getTags();

}