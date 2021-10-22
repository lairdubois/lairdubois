<?php

namespace App\Utils;

use CommerceGuys\Addressing\Address;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use App\Model\LocalisableExtendedInterface;
use App\Model\LocalisableInterface;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Query\GeocodeQuery;
use Http\Adapter\Guzzle6\Client;
use Psr\Log\LoggerInterface;

class LocalisableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.localisable_utils';

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), array(
            'logger' => '?'.LoggerInterface::class,
        ));
    }

	/////

	public function geocodeLocation(LocalisableInterface $localisable) {
		if (!is_null($localisable->getLocation())) {

			$googleApiKey = $this->getParameter('google_api_key');
			$adapter  = new Client();
			$geocoder = new GoogleMaps($adapter, 'France', $googleApiKey);

			try {
				$response = $geocoder->geocodeQuery(GeocodeQuery::create($localisable->getLocation()));
			} catch (\Exception $e) {
				$this->get('logger')->error($e);
				return false;
			}

			if ($response->count() > 0) {

				$address = $response->first();

				// Location
				$localisable->setLatitude($address->getCoordinates()->getLatitude());
				$localisable->setLongitude($address->getCoordinates()->getLongitude());

				if ($localisable instanceof LocalisableExtendedInterface) {

					// PostalCode /////

					$postalCode = $address->getPostalCode();
					if ($postalCode) {
						$localisable->setPostalCode($postalCode);
					} else {
						$localisable->setPostalCode(null);
					}

					// Locality /////

					$locality = $address->getLocality();
					if ($locality) {
						$localisable->setLocality($locality);
					} else {
						$localisable->setLocality(null);
					}

					// Country /////

					$country = $address->getCountry();
					if ($country) {
						$localisable->setCountry($country->getName());
					} else {
						$localisable->setCountry(null);
					}

					// GeographicalAreas /////

					$geographicalAreaParts = array();

					if ($locality) {
						$geographicalAreaParts[] = $locality;
					}
					$adminLevels = $address->getAdminLevels();
					foreach ($adminLevels as $adminLevel) {
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
					$formatter = new DefaultFormatter($addressFormatRepository, $countryRepository, $subdivisionRepository, array( 'locale' => '\'fr_FR\'', 'html' => false ));

					$a = new Address();

					$a = $a->withCountryCode($address->getCountry()->getCode());
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

					try {
						$localisable->setFormattedAddress($formatter->format($a));
					} catch (\Exception $e) {}

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

	public function getBoundsAndLocation($address) {

		$googleApiKey = $this->getParameter('google_api_key');
		try {

			$url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&key='.$googleApiKey.'&language=fr&region=FR';
			$contents = file_get_contents($url);
			$hash = json_decode($contents, true);

			if ($hash && isset($hash['results']) && is_array($hash['results'])) {

				foreach ($hash['results'] as $result) {

					if (isset($result['geometry'])) {

						$boundsAndLocation = array();

						if (isset($result['geometry']['bounds'])) {

							$bounds = $result['geometry']['bounds'];

							// Returns an Elasticsearch ready bounds array [ top_left, bottom_right ]
							$boundsAndLocation['bounds'] = array(
								$bounds['northeast']['lat'].','.$bounds['southwest']['lng'],
								$bounds['southwest']['lat'].','.$bounds['northeast']['lng'],
							);
						}

						if (isset($result['geometry']['location'])) {

							$location = $result['geometry']['location'];

							// Returns an Elasticsearch ready geopoint string "lat,lng"
							$boundsAndLocation['location'] = $location['lat'].','.$location['lng'];
						}

						return $boundsAndLocation;

					}

				}

			}

		} catch (\Exception $e) {
			$this->get('logger')->error($e);
		}

		return null;
	}

}