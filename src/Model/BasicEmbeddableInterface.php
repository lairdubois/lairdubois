<?php

namespace App\Model;

interface BasicEmbeddableInterface extends TypableInterface, IdentifiableInterface, TitledInterface, AuthoredInterface {

	// Sticker /////

	public function setSticker(\App\Entity\Core\Picture $sticker);

	public function getSticker();

}
