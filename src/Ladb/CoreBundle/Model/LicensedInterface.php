<?php

namespace Ladb\CoreBundle\Model;

interface LicensedInterface {

	// License /////

	public function setLicense($license);

	public function getLicense();

}