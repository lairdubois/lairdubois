<?php

namespace App\Model;

interface MultiPicturedInterface {

	// Pictures /////

	public function addPicture(\App\Entity\Core\Picture $picture);

	public function removePicture(\App\Entity\Core\Picture $picture);

	public function getPictures();

	public function resetPictures();

}
