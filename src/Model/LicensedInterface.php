<?php

namespace App\Model;

interface LicensedInterface {

	// License /////

	public function setLicense($license);

	public function getLicense();

}