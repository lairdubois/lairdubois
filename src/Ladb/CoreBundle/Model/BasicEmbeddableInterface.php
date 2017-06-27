<?php

namespace Ladb\CoreBundle\Model;

interface BasicEmbeddableInterface extends TypableInterface, IdentifiableInterface, TitledInterface, AuthoredInterface {

	// Sticker /////

	public function setSticker(\Ladb\CoreBundle\Entity\Core\Picture $sticker);

	public function getSticker();

}
