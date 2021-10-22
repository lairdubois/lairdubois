<?php

namespace App\Model;

trait TaggableTrait {

	// Tags /////

	public function addTag(\App\Entity\Core\Tag $tag) {
		$this->tags[] = $tag;
		return $this;
	}

	public function removeTag(\App\Entity\Core\Tag $tag) {
		$this->tags->removeElement($tag);
	}

	public function getTags() {
		return $this->tags;
	}

}