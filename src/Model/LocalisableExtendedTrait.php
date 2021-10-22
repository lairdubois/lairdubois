<?php

namespace App\Model;

trait LocalisableExtendedTrait {

	// PostalCode /////

	public function setPostalCode($postalCode = null) {
		$this->postalCode = $postalCode;
		return $this;
	}

	public function getPostalCode() {
		return $this->postalCode;
	}

	// Locality /////

	public function setLocality($locality = null) {
		$this->locality = $locality;
		return $this;
	}

	public function getLocality() {
		return $this->locality;
	}

	// Country /////

	public function setCountry($country = null) {
		$this->country = $country;
		return $this;
	}

	public function getCountry() {
		return $this->country;
	}

	// GeographicalAreas /////

	public function setGeographicalAreas($geographicalAreas = null) {
		$this->geographicalAreas = $geographicalAreas;
		return $this;
	}

	public function getGeographicalAreas() {
		return $this->geographicalAreas;
	}

	// FormattedAddress /////

	public function setFormattedAddress($formattedAddress = null) {
		return $this->formattedAdress = $formattedAddress;
	}

	public function getFormattedAddress() {
		return $this->formattedAdress;
	}

}