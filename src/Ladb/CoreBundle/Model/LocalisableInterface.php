<?php

namespace Ladb\CoreBundle\Model;

interface LocalisableInterface {

	// Location /////

	public function setLocation($location);

	public function getLocation();

	// Latitude /////

	public function setLatitude($latitude = null);

	public function getLatitude();

	// Longitude /////

	public function setLongitude($longitude = null);

	public function getLongitude();

	// GeoPoint /////

	public function getGeoPoint();

}
