<?php

namespace App\Model;

interface StripableInterface extends TypableInterface, IdentifiableInterface {

	// Strip /////

	public function setStrip(\App\Entity\Core\Picture $strip);

	public function getStrip();

}
