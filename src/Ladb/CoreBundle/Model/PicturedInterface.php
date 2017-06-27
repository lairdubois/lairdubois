<?php

namespace Ladb\CoreBundle\Model;

interface PicturedInterface {

	// MainPicture /////

	public function setMainPicture(\Ladb\CoreBundle\Entity\Core\Picture $mainPicture);

	public function getMainPicture();

}
