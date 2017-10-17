<?php

namespace Ladb\CoreBundle\Utils;

use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Model\Address;
use CommerceGuys\Addressing\Repository\AddressFormatRepository;
use CommerceGuys\Addressing\Repository\CountryRepository;
use CommerceGuys\Addressing\Repository\SubdivisionRepository;
use Ivory\GoogleMap\Overlays\InfoWindow;
use Ivory\GoogleMap\Events\MouseEvent;
use Ivory\GoogleMap\Services\Geocoding\GeocoderRequest;
use Ladb\CoreBundle\Model\LocalisableExtendedInterface;
use Ladb\CoreBundle\Model\LocalisableInterface;

class LocalisableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.localisable_utils';

	/////

	public function geocodeLocation(LocalisableInterface $localisable) {
		if (!is_null($localisable->getLocation())) {

			$googleApiKey = $this->getParameter('google_api_key');
			$adapter  = new \Ivory\HttpAdapter\CurlHttpAdapter();
			$geocoder = new \Geocoder\Provider\GoogleMaps($adapter, null, null, true, $googleApiKey);
			$geocoder->setLocale('fr_FR');

			$response = $geocoder->geocode($localisable->getLocation());

			if ($response->count() > 0) {

				$address = $response->first();

				// Location
				$localisable->setLatitude($address->getLatitude());
				$localisable->setLongitude($address->getLongitude());

				if ($localisable instanceof LocalisableExtendedInterface) {

					// PostalCode /////

					$postalCode = $address->getPostalCode();
					if ($postalCode) {
						$localisable->setPostalCode($postalCode);
					}

					// Locality /////

					$locality = $address->getLocality();
					if ($locality) {
						$localisable->setLocality($locality);
					}

					// Country /////

					$country = $address->getCountry();
					if ($country) {
						$localisable->setCountry($country->getName());
					}

					// GeographicalAreas /////

					$geographicalAreaParts = array();

					if ($locality) {
						$geographicalAreaParts[] = $locality;
					}
					$adminLevels = $address->getAdminLevels();
					for ($i = $adminLevels->count(); $i > 0; $i--) {
						$adminLevel = $adminLevels->get($i);
						$geographicalAreaParts[] = $adminLevel->getName();
					}
					if ($country) {
						$geographicalAreaParts[] = $country->getName();
					}

					if (!empty($geographicalAreaParts)) {
						$localisable->setGeographicalAreas(implode(',', $geographicalAreaParts));
					} else {
						$localisable->setGeographicalAreas(null);
					}

					// FormattedAddress /////

					$addressFormatRepository = new AddressFormatRepository();
					$countryRepository = new CountryRepository();
					$subdivisionRepository = new SubdivisionRepository();
					$formatter = new DefaultFormatter($addressFormatRepository, $countryRepository, $subdivisionRepository, 'fr_FR', array( 'html' => false ));

					$a = new Address();

					$a = $a->withCountryCode($address->getCountryCode());
					if ($address->getStreetNumber() && $address->getStreetName()) {
						$a = $a->withAddressLine1($address->getStreetNumber().' '.$address->getStreetName());
					} else if ($address->getStreetName()) {
						$a = $a->withAddressLine1($address->getStreetName());
					}
					if ($postalCode) {
						$a = $a->withPostalCode($postalCode);
					}
					if ($locality) {
						$a = $a->withLocality($locality);
					}
					if ($address->getSubLocality()) {
						$a = $a->withDependentLocality($address->getSubLocality());
					}
					if ($address->getAdminLevels()->first()) {
						$a = $a->withAdministrativeArea($address->getAdminLevels()->first()->getName());
					}

					$localisable->setFormattedAddress($formatter->format($a));

				}

				return true;
			}

		} else {
			$localisable->setLatitude(null);
			$localisable->setLongitude(null);
			if ($localisable instanceof LocalisableExtendedInterface) {
				$localisable->setGeographicalAreas(null);
				$localisable->setPostalCode(null);
				$localisable->setLocality(null);
				$localisable->setFormattedAddress($localisable->getLocation());
			}
		}

		return false;
	}

	/////

	public function getTopLeftBottomRightBounds($address) {

		$googleApiKey = $this->getParameter('google_api_key');
		$hash = json_decode(file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$googleApiKey), true);

		if ($hash && isset($hash['results']) && isset($hash['results'][0]) && isset($hash['results'][0]['geometry']) && isset($hash['results'][0]['geometry']['bounds'])) {
			$bounds = $hash['results'][0]['geometry']['bounds'];

			// Returns an Elasticsearch ready bounds array [ top_left, bottom_right ]
			return array(
				$bounds['northeast']['lat'].','.$bounds['southwest']['lng'],
				$bounds['southwest']['lat'].','.$bounds['northeast']['lng'],
			);
		}

		return null;
	}

}