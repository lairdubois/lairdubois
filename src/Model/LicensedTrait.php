<?php

namespace App\Model;

trait LicensedTrait {

	// License /////

	public function setLicense($license) {
		$this->license = $license;
	}

	public function getLicense() {
		if (is_null($this->license)) {
			return new \App\Entity\Core\License();
		}
		return $this->license;
	}

}