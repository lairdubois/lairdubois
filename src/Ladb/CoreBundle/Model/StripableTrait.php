<?php

namespace Ladb\CoreBundle\Model;

trait StripableTrait {

// Strip /////

	public function setStrip(\Ladb\CoreBundle\Entity\Core\Picture $strip = null) {
		$this->strip = $strip;
		return $this;
	}

	public function getStrip() {
		return $this->strip;
	}

}

