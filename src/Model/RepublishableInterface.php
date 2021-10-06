<?php

namespace App\Model;

interface RepublishableInterface {

	// PublishCount /////

	public function incrementPublishCount($by = 1);

	public function setPublishCount($publishCount);

	public function getPublishCount();

}
