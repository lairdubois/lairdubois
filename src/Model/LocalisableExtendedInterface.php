<?php

namespace App\Model;

interface LocalisableExtendedInterface extends LocalisableInterface {

	// PostalCode /////

	public function setPostalCode($postalCode = null);

	public function getPostalCode();

	// Locality /////

	public function setLocality($locality = null);

	public function getLocality();

	// Country /////

	public function setCountry($country = null);

	public function getCountry();

	// GeographicalAreas /////

	public function setGeographicalAreas($geographicalAreas = null);

	public function getGeographicalAreas();

	// FormattedAddress /////

	public function setFormattedAddress($formattedAddress = null);

	public function getFormattedAddress();

}
