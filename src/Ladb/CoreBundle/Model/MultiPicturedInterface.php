<?php

namespace Ladb\CoreBundle\Model;

interface MultiPicturedInterface {

	// Pictures /////

	public function addPicture(\Ladb\CoreBundle\Entity\Picture $picture);

	public function removePicture(\Ladb\CoreBundle\Entity\Picture $picture);

	public function getPictures();

	public function resetPictures();

}
