<?php

namespace Ladb\CoreBundle\Model;

trait LicensedTrait {

	// License /////

	public function setLicense($license) {
		$this->license = $license;
	}

	public function getLicense() {
		if (is_null($this->license)) {
			return new \Ladb\CoreBundle\Entity\License();
		}
		return $this->license;
	}

}