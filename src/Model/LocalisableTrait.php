<?php

namespace App\Model;

trait LocalisableTrait {

	// Location /////

	public function setLocation($location) {
		$this->location = $location;
		return $this;
	}

	public function getLocation() {
		return $this->location;
	}

	// Latitude /////

	public function setLatitude($latitude = null) {
		$this->latitude = $latitude;
	}

	public function getLatitude() {
		return $this->latitude;
	}

	// Longitude /////

	public function setLongitude($longitude = null) {
		$this->longitude = $longitude;
	}

	public function getLongitude() {
		return $this->longitude;
	}

	// GeoPoint /////

	public function getGeoPoint() {
		if (!is_null($this->latitude) && !is_null($this->longitude)) {
			return array( $this->longitude, $this->latitude );
		}
		return null;
	}

}