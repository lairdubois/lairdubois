<?php

namespace App\Model;

interface PicturedInterface {

	// MainPicture /////

	public function setMainPicture(\App\Entity\Core\Picture $mainPicture);

	public function getMainPicture();

}
