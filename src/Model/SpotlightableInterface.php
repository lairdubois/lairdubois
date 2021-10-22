<?php

namespace App\Model;

interface SpotlightableInterface {

	// Spotlight /////

	public function getSpotlight();

	public function setSpotlight(\App\Entity\Core\Spotlight $spotlight = null);

	public function withEnabledSpotlight();

}
