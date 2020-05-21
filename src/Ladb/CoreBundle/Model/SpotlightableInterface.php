<?php

namespace Ladb\CoreBundle\Model;

interface SpotlightableInterface {

	// Spotlight /////

	public function getSpotlight();

	public function setSpotlight(\Ladb\CoreBundle\Entity\Core\Spotlight $spotlight = null);

	public function withEnabledSpotlight();

}
