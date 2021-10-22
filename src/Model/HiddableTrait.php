<?php

namespace App\Model;

trait HiddableTrait {

	// Visibility /////

	public function setVisibility($visibility) {
		$this->visibility = $visibility;
		return $this;
	}

	public function getVisibility() {
		return $this->visibility;
	}

	public function getIsPrivate() {
		return $this->getVisibility() == HiddableInterface::VISIBILITY_PRIVATE;
	}

	public function getIsProtected() {
		return $this->getVisibility() == HiddableInterface::VISIBILITY_PROTECTED;
	}

	public function getIsPublic() {
		return $this->getVisibility() == HiddableInterface::VISIBILITY_PUBLIC;
	}

}