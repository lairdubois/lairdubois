<?php

namespace Ladb\CoreBundle\Model;

trait TaggableTrait {

	// Tags /////

	public function addTag(\Ladb\CoreBundle\Entity\Core\Tag $tag) {
		$this->tags[] = $tag;
		return $this;
	}

	public function removeTag(\Ladb\CoreBundle\Entity\Core\Tag $tag) {
		$this->tags->removeElement($tag);
	}

	public function getTags() {
		return $this->tags;
	}

}