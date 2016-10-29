<?php

namespace Ladb\CoreBundle\Model;

interface StripableInterface extends TypableInterface, IdentifiableInterface {

	// Strip /////

	public function setStrip(\Ladb\CoreBundle\Entity\Picture $strip);

	public function getStrip();

}
