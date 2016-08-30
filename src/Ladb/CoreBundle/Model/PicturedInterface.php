<?php

namespace Ladb\CoreBundle\Model;

interface PicturedInterface {

	// MainPicture /////

	public function setMainPicture(\Ladb\CoreBundle\Entity\Picture $mainPicture);

	public function getMainPicture();

}
